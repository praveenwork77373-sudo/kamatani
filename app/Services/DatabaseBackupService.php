<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use App\Models\DatabaseBackupSetting;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public const BACKUP_DIR = 'backups';

    /**
     * Get or create the singleton settings row.
     */
    public function getSettings(): DatabaseBackupSetting
    {
        $settings = DatabaseBackupSetting::query()->first();

        if ($settings) {
            return $settings;
        }

        return DatabaseBackupSetting::create([
            'enabled' => false,
            'frequency' => 'daily',
            'time' => '02:00',
            'day_of_week' => null,
            'day_of_month' => null,
            'retain_days' => 30,
        ]);
    }

    public function updateSettings(array $data, ?int $userId = null): DatabaseBackupSetting
    {
        $settings = $this->getSettings();

        $settings->fill([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'frequency' => $data['frequency'] ?? 'daily',
            'time' => $data['time'] ?? '02:00',
            'day_of_week' => ($data['frequency'] ?? '') === 'weekly'
                ? ($data['day_of_week'] ?? 0)
                : null,
            'day_of_month' => ($data['frequency'] ?? '') === 'monthly'
                ? ($data['day_of_month'] ?? 1)
                : null,
            'retain_days' => (int) ($data['retain_days'] ?? 30),
            'updated_by_user_id' => $userId,
        ]);
        $settings->save();

        return $settings;
    }

    /**
     * @return LengthAwarePaginator<int, DatabaseBackup>
     */
    public function listBackups(int $perPage = 10): LengthAwarePaginator
    {
        return DatabaseBackup::query()
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createBackup(?int $userId = null, string $type = 'manual'): DatabaseBackup
    {
        $this->ensureBackupDirectory();

        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");
        $extension = in_array($driver, ['mysql', 'mariadb'], true) ? 'sql' : 'sql';
        $filename = sprintf(
            'backup_%s_%s.%s',
            $driver,
            now()->format('Ymd_His'),
            $extension
        );
        $relativePath = self::BACKUP_DIR . '/' . $filename;
        $absolutePath = storage_path('app/' . $relativePath);

        $backup = DatabaseBackup::create([
            'filename' => $filename,
            'path' => $relativePath,
            'size_bytes' => 0,
            'type' => $type === 'scheduled' ? 'scheduled' : 'manual',
            'status' => 'pending',
            'created_by_user_id' => $userId,
        ]);

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->dumpMysql($connection, $absolutePath);
            } elseif ($driver === 'sqlite') {
                $this->dumpSqlite($connection, $absolutePath);
            } else {
                throw new RuntimeException("サポートされていないデータベースドライバです: {$driver}");
            }

            if (! is_file($absolutePath) || filesize($absolutePath) === 0) {
                throw new RuntimeException('バックアップファイルが空、または作成されませんでした。');
            }

            $backup->update([
                'status' => 'success',
                'size_bytes' => filesize($absolutePath),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'size_bytes' => 0,
            ]);

            Log::error('Database backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $backup->fresh();
    }

    /**
     * Restore database from a successful backup file.
     * WARNING: This overwrites the current database.
     */
    public function restore(DatabaseBackup $backup): void
    {
        if ($backup->status !== 'success') {
            throw new RuntimeException('成功したバックアップのみ復元できます。');
        }

        $absolutePath = $backup->absolutePath();
        if (! is_file($absolutePath)) {
            throw new RuntimeException('バックアップファイルが見つかりません。');
        }

        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->restoreMysql($connection, $absolutePath);
        } elseif ($driver === 'sqlite') {
            $this->restoreSqlite($connection, $absolutePath);
        } else {
            throw new RuntimeException("サポートされていないデータベースドライバです: {$driver}");
        }
    }

    public function deleteBackup(DatabaseBackup $backup): void
    {
        $path = $backup->absolutePath();
        if (is_file($path)) {
            @unlink($path);
        }
        $backup->delete();
    }

    /**
     * Delete successful backups older than retain_days.
     */
    public function pruneOldBackups(?int $retainDays = null): int
    {
        $days = $retainDays ?? $this->getSettings()->retain_days;
        $days = max(1, (int) $days);
        $cutoff = now()->subDays($days);

        $old = DatabaseBackup::query()
            ->where('status', 'success')
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;
        foreach ($old as $backup) {
            $this->deleteBackup($backup);
            $deleted++;
        }

        // Also remove orphaned failed records older than retain period
        DatabaseBackup::query()
            ->where('status', 'failed')
            ->where('created_at', '<', $cutoff)
            ->delete();

        return $deleted;
    }

    /**
     * Whether a scheduled backup should run now based on settings.
     */
    public function shouldRunNow(?Carbon $now = null): bool
    {
        $settings = $this->getSettings();
        if (! $settings->enabled) {
            return false;
        }

        $now = $now ?? now();
        $targetTime = $settings->time ?: '02:00';

        if ($now->format('H:i') !== $targetTime) {
            return false;
        }

        // Avoid running twice in the same scheduled slot
        if ($settings->last_run_at && $settings->last_run_at->gt($now->copy()->subMinutes(55))) {
            return false;
        }

        return match ($settings->frequency) {
            'daily' => true,
            'weekly' => (int) $now->dayOfWeek === (int) ($settings->day_of_week ?? 0),
            'monthly' => (int) $now->day === (int) ($settings->day_of_month ?? 1),
            default => false,
        };
    }

    /**
     * Run scheduled backup if due. Returns the backup or null if skipped.
     */
    public function runScheduledIfDue(): ?DatabaseBackup
    {
        if (! $this->shouldRunNow()) {
            return null;
        }

        $backup = $this->createBackup(null, 'scheduled');

        $settings = $this->getSettings();
        $settings->last_run_at = now();
        $settings->save();

        $this->pruneOldBackups();

        return $backup;
    }

    protected function ensureBackupDirectory(): void
    {
        $dir = storage_path('app/' . self::BACKUP_DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function dumpMysql(array $connection, string $absolutePath): void
    {
        $mysqldump = $this->findBinary('mysqldump', [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ]);

        $host = $connection['host'] ?? '127.0.0.1';
        $port = (string) ($connection['port'] ?? '3306');
        $database = $connection['database'] ?? '';
        $username = $connection['username'] ?? 'root';
        $password = (string) ($connection['password'] ?? '');

        $command = [
            $mysqldump,
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--result-file=' . $absolutePath,
            $database,
        ];

        $this->runProcess($command, ['MYSQL_PWD' => $password]);
    }

    protected function restoreMysql(array $connection, string $absolutePath): void
    {
        $mysql = $this->findBinary('mysql', [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            '/opt/homebrew/bin/mysql',
        ]);

        $host = $connection['host'] ?? '127.0.0.1';
        $port = (string) ($connection['port'] ?? '3306');
        $database = $connection['database'] ?? '';
        $username = $connection['username'] ?? 'root';
        $password = (string) ($connection['password'] ?? '');

        $command = [
            $mysql,
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--default-character-set=utf8mb4',
            $database,
        ];

        $process = new Process($command);
        $process->setTimeout(600);
        $process->setEnv(array_merge($_ENV, ['MYSQL_PWD' => $password]));
        $process->setInput(file_get_contents($absolutePath));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'データベースの復元に失敗しました: ' . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }
    }

    protected function dumpSqlite(array $connection, string $absolutePath): void
    {
        $database = $connection['database'] ?? '';
        if (! is_file($database)) {
            throw new RuntimeException("SQLite データベースファイルが見つかりません: {$database}");
        }

        $sqlite3 = $this->findBinaryOptional('sqlite3', [
            'C:\\xampp\\sqlite\\sqlite3.exe',
            '/usr/bin/sqlite3',
            '/usr/local/bin/sqlite3',
        ]);

        if ($sqlite3) {
            $command = [$sqlite3, $database, '.dump'];
            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(
                    'SQLite ダンプに失敗しました: ' . trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            file_put_contents($absolutePath, $process->getOutput());

            return;
        }

        // Fallback: copy the sqlite file with .sql extension content as binary dump note
        // Prefer PHP PDO dump of schema+data via .dump alternative — copy DB file
        if (! copy($database, $absolutePath)) {
            throw new RuntimeException('SQLite ファイルのコピーに失敗しました。');
        }
    }

    protected function restoreSqlite(array $connection, string $absolutePath): void
    {
        $database = $connection['database'] ?? '';
        $dir = dirname($database);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Detect binary SQLite file vs SQL dump text
        $header = file_get_contents($absolutePath, false, null, 0, 32) ?: '';

        if (str_starts_with($header, 'SQLite format 3')) {
            if (! copy($absolutePath, $database)) {
                throw new RuntimeException('SQLite ファイルの復元に失敗しました。');
            }

            return;
        }

        $sqlite3 = $this->findBinaryOptional('sqlite3', [
            'C:\\xampp\\sqlite\\sqlite3.exe',
            '/usr/bin/sqlite3',
            '/usr/local/bin/sqlite3',
        ]);

        if ($sqlite3) {
            // Replace DB: remove then import
            if (is_file($database)) {
                @unlink($database);
            }
            $command = [$sqlite3, $database];
            $process = new Process($command);
            $process->setTimeout(300);
            $process->setInput(file_get_contents($absolutePath));
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(
                    'SQLite 復元に失敗しました: ' . trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            return;
        }

        // Fallback binary/file copy
        if (! copy($absolutePath, $database)) {
            throw new RuntimeException('SQLite ファイルの復元に失敗しました（sqlite3 未インストール）。');
        }
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     */
    protected function runProcess(array $command, array $env = []): void
    {
        $process = new Process($command);
        $process->setTimeout(600);
        if ($env !== []) {
            $process->setEnv(array_merge($_ENV, $env));
        }
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException($error !== '' ? $error : 'コマンドの実行に失敗しました。');
        }
    }

    /**
     * @param  list<string>  $extraPaths
     */
    protected function findBinary(string $name, array $extraPaths = []): string
    {
        $found = $this->findBinaryOptional($name, $extraPaths);
        if ($found) {
            return $found;
        }

        throw new RuntimeException(
            "{$name} コマンドが見つかりません。PATH を確認するか、XAMPP の場合は C:\\xampp\\mysql\\bin を確認してください。"
        );
    }

    /**
     * @param  list<string>  $extraPaths
     */
    protected function findBinaryOptional(string $name, array $extraPaths = []): ?string
    {
        $candidates = array_merge([$name], $extraPaths);

        // which / where
        if (PHP_OS_FAMILY === 'Windows') {
            $which = new Process(['where', $name]);
        } else {
            $which = new Process(['which', $name]);
        }
        $which->run();
        if ($which->isSuccessful()) {
            $line = trim(explode("\n", str_replace("\r", '', $which->getOutput()))[0] ?? '');
            if ($line !== '' && (is_file($line) || $this->isExecutableName($line))) {
                array_unshift($candidates, $line);
            }
        }

        foreach ($candidates as $candidate) {
            if ($candidate === $name) {
                // Let Process try PATH resolution by name alone later — verify via --version if possible
                $probe = new Process([$name, '--version']);
                $probe->setTimeout(10);
                try {
                    $probe->run();
                    if ($probe->isSuccessful() || $probe->getExitCode() === 0) {
                        return $name;
                    }
                } catch (Throwable) {
                    // continue
                }
                continue;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function isExecutableName(string $path): bool
    {
        return is_executable($path) || (PHP_OS_FAMILY === 'Windows' && is_file($path));
    }
}
