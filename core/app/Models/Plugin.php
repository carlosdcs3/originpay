<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'credentials',
        'fields',
        'status',
    ];

    public static function credentials($code): mixed
    {
        return Cache::rememberForever($code, function () use ($code) {
            $plugin = self::where('name', $code)
                ->orWhere('type', $code)
                ->first();

            if (!$plugin) {
                return ['status' => 0];
            }

            $credentials = json_decode($plugin->credentials ?? '[]', true);

            if (!is_array($credentials)) {
                $credentials = [];
            }

            $credentials['status'] = $plugin->status;

            return $credentials;
        });
    }

    protected static function boot(): void
    {
        parent::boot();

        static::updated(function ($plugin) {
            self::flushCache($plugin->code);
        });
    }

    private static function flushCache($code): void
    {
        Cache::forget($code);
        Cache::forget('plugins_data');
    }
}
