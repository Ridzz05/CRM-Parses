<?php

namespace App\Support;

use Illuminate\Support\Str;

class Platform
{
    public const SUPPORTED = [
        'web',
        'telegram',
        'whatsapp_business',
        'instagram',
        'other',
    ];

    public static function normalize(?string $value, string $fallback = 'other'): string
    {
        $normalized = Str::of((string) $value)
            ->lower()
            ->trim()
            ->replace(['-', '.', '/', '\\'], '_')
            ->replace(' ', '_')
            ->squish()
            ->toString();

        $aliases = [
            'web' => 'web',
            'dashboard' => 'web',
            'manual' => 'web',
            'telegram' => 'telegram',
            'tg' => 'telegram',
            'tele' => 'telegram',
            'wa' => 'whatsapp_business',
            'whatsapp' => 'whatsapp_business',
            'whatsapp_business' => 'whatsapp_business',
            'wa_business' => 'whatsapp_business',
            'whatsapp_bisnis' => 'whatsapp_business',
            'wa_bisnis' => 'whatsapp_business',
            'waba' => 'whatsapp_business',
            'ig' => 'instagram',
            'insta' => 'instagram',
            'instagram' => 'instagram',
            'other' => 'other',
            'lainnya' => 'other',
        ];

        return $aliases[$normalized] ?? (in_array($fallback, self::SUPPORTED, true) ? $fallback : 'other');
    }
}
