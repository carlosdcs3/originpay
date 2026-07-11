<?php

namespace App\Domain\Core;

use Illuminate\Support\Str;

class EventContext
{
    protected static ?string $correlationId = null;
    protected static ?string $causationId = null;
    protected static ?string $actorId = null;
    protected static ?string $actorType = null;

    public static function setCorrelationId(string $id): void
    {
        self::$correlationId = $id;
    }

    public static function getCorrelationId(): string
    {
        if (!self::$correlationId) {
            self::$correlationId = (string) Str::uuid();
        }
        return self::$correlationId;
    }

    public static function setCausationId(?string $id): void
    {
        self::$causationId = $id;
    }

    public static function getCausationId(): ?string
    {
        return self::$causationId;
    }

    public static function setActor(?string $type, ?string $id): void
    {
        self::$actorType = $type;
        self::$actorId = $id;
    }

    public static function getActorType(): ?string
    {
        return self::$actorType;
    }

    public static function getActorId(): ?string
    {
        return self::$actorId;
    }

    public static function clear(): void
    {
        self::$correlationId = null;
        self::$causationId = null;
        self::$actorId = null;
        self::$actorType = null;
    }
}
