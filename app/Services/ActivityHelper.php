<?php
declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class ActivityHelper
{
    public static function getActivityClass(?string $dateString): string
    {
        if (!$dateString) {
            return 'activity-old';
        }

        $days = Carbon::parse($dateString)->diffInDays(now());

        if ($days > 14) {
            return 'activity-old';
        }

        return 'activity-' . min((int) $days, 14);
    }
}
