<?php

namespace Database\Seeders\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class DemoPeriod
{
    private static ?CarbonImmutable $lastDay = null;

    /** Keep the same 30-day window while individual transactions use historical clocks. */
    public static function run(callable $callback): mixed
    {
        $previous = self::$lastDay;
        self::$lastDay = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        try {
            return $callback();
        } finally {
            self::$lastDay = $previous;
        }
    }

    public static function start(): CarbonImmutable
    {
        return self::end()->subDays(29)->startOfDay();
    }

    public static function end(): CarbonImmutable
    {
        self::$lastDay ??= CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        return self::$lastDay->endOfDay();
    }

    public static function day(int $offset, int $hour = 0, int $minute = 0): CarbonImmutable
    {
        if ($offset < 0 || $offset > 29) {
            throw new InvalidArgumentException('Hari dummy harus berada dalam rentang 0 sampai 29.');
        }

        return self::start()->addDays($offset)->setTime($hour, $minute);
    }

    /** Date columns stay in WIB; automatic timestamps use the application's storage timezone. */
    public static function at(CarbonInterface $date, callable $callback): mixed
    {
        self::end();
        $previous = Carbon::getTestNow();
        Carbon::setTestNow($date);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
