<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
        });

        if (! Schema::hasTable('welcome_email_send_histories')) {
            Schema::create('welcome_email_send_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('medical_institution_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('destination_email');
                $table->string('status', 20)->default('sent'); // sent | failed
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('applications', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('applications', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('shipped_at');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Expand enum, migrate approved → in_progress, then drop approved
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
                'pending',
                'waiting_special_approval',
                'approved',
                'in_progress',
                'completed',
                'rejected',
                'cancel_request',
                'cancel_approved',
                'cancel_rejected'
            ) NOT NULL DEFAULT 'pending'");

            DB::table('applications')->where('status', 'approved')->update([
                'status' => 'in_progress',
                'accepted_at' => DB::raw('COALESCE(accepted_at, approved_at, NOW())'),
            ]);

            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
                'pending',
                'waiting_special_approval',
                'in_progress',
                'completed',
                'rejected',
                'cancel_request',
                'cancel_approved',
                'cancel_rejected'
            ) NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check');
            DB::table('applications')->where('status', 'approved')->update([
                'status' => 'in_progress',
            ]);
            DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN (
                'pending',
                'waiting_special_approval',
                'in_progress',
                'completed',
                'rejected',
                'cancel_request',
                'cancel_approved',
                'cancel_rejected'
            ))");
        } else {
            // sqlite / others: string column — just remap values
            DB::table('applications')->where('status', 'approved')->update([
                'status' => 'in_progress',
            ]);
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
                'pending',
                'waiting_special_approval',
                'approved',
                'in_progress',
                'completed',
                'rejected',
                'cancel_request',
                'cancel_approved',
                'cancel_rejected'
            ) NOT NULL DEFAULT 'pending'");

            DB::table('applications')->where('status', 'in_progress')->update(['status' => 'approved']);
            DB::table('applications')->where('status', 'completed')->update(['status' => 'approved']);

            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
                'pending',
                'waiting_special_approval',
                'approved',
                'rejected',
                'cancel_request',
                'cancel_approved',
                'cancel_rejected'
            ) NOT NULL DEFAULT 'pending'");
        }

        Schema::table('applications', function (Blueprint $table) {
            foreach (['accepted_at', 'shipped_at', 'completed_at'] as $col) {
                if (Schema::hasColumn('applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('welcome_email_send_histories');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};
