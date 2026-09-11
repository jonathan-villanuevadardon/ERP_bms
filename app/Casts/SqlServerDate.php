<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Normaliza el formato de fecha que pdo_dblib recibe desde FreeTDS.
 */
class SqlServerDate implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $this->convertir($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $this->convertir($value)?->format('Y-m-d');
    }

    private function convertir(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        // FreeTDS entrega DATE como "Sep 10 2026 12:00:00:AM".
        $normalizado = preg_replace('/:(AM|PM)$/i', ' $1', trim((string) $value));

        return CarbonImmutable::parse($normalizado);
    }
}
