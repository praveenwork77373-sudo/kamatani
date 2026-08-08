<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UniformCategory extends Model
{
    use HasFactory;

    public const SIZE_LETTER = 'letter';

    public const SIZE_SHOE = 'shoe';

    public const SIZE_NONE = 'none';

    protected $fillable = [
        'code',
        'name',
        'size_scheme',
        'employee_size_field',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCache());
        static::deleted(fn () => self::forgetCache());
    }

    public function products(): HasMany
    {
        return $this->hasMany(UniformProduct::class, 'category', 'code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function usesShoeSizes(): bool
    {
        return $this->size_scheme === self::SIZE_SHOE;
    }

    public function usesLetterSizes(): bool
    {
        return $this->size_scheme === self::SIZE_LETTER;
    }

    /**
     * @return Collection<int, self>
     */
    public static function activeOrdered(): Collection
    {
        return Cache::remember('uniform_categories.active', 60, function () {
            return static::query()->active()->ordered()->get();
        });
    }

    /**
     * @return array<int, string>
     */
    public static function activeCodes(): array
    {
        return static::activeOrdered()->pluck('code')->all();
    }

    public static function findByCode(?string $code): ?self
    {
        if ($code === null || $code === '') {
            return null;
        }

        return static::activeOrdered()->firstWhere('code', $code)
            ?? static::query()->where('code', $code)->first();
    }

    public static function labelFor(?string $code): string
    {
        $cat = static::findByCode($code);

        return $cat?->name ?? (string) $code;
    }

    public static function sizeSchemeFor(?string $code): string
    {
        return static::findByCode($code)?->size_scheme ?? self::SIZE_LETTER;
    }

    /**
     * @return array<int, string>
     */
    public static function codesWithScheme(string $scheme): array
    {
        return static::activeOrdered()
            ->where('size_scheme', $scheme)
            ->pluck('code')
            ->values()
            ->all();
    }

    public static function forgetCache(): void
    {
        Cache::forget('uniform_categories.active');
    }
}
