<?php
// The booking rules, in one place so customer and staff booking use the same checks:
//   1. no overlapping bookings for the same stylist
//   2. daily appointment limit
//   3. cancelled and no-show bookings free their slot
//   4. no bookings in the past, only inside opening hours
//   5. inactive services or stylists cannot be booked

declare(strict_types=1);

final class BookingRules
{
    /** Statuses that hold a stylist's time slot. */
    private const BLOCKING = ['pending', 'confirmed'];

    /** Statuses that count toward the daily limit. */
    private const COUNTED = ['pending', 'confirmed', 'completed'];

    /** '14:00' + 45 minutes => '14:45:00' */
    public static function endTime(string $start, int $minutes): string
    {
        return date('H:i:s', strtotime($start) + $minutes * 60);
    }

    /** Active service, or null. */
    public static function service(int $serviceId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM services WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $serviceId]);
        return $stmt->fetch() ?: null;
    }

    /** Active stylist who performs the service, or null. */
    public static function stylistFor(int $stylistId, int $serviceId): ?array
    {
        $stmt = db()->prepare(
            'SELECT st.* FROM stylists st
               JOIN stylist_services ss ON ss.stylist_id = st.id AND ss.service_id = :service
              WHERE st.id = :id AND st.is_active = 1'
        );
        $stmt->execute(['id' => $stylistId, 'service' => $serviceId]);
        return $stmt->fetch() ?: null;
    }

    /** Active services that at least one active stylist can perform. */
    public static function bookableServices(): array
    {
        return db()->query(
            'SELECT s.id, s.name, s.category, s.price, s.duration_minutes
               FROM services s
              WHERE s.is_active = 1
                AND EXISTS (SELECT 1 FROM stylist_services ss
                              JOIN stylists st ON st.id = ss.stylist_id AND st.is_active = 1
                             WHERE ss.service_id = s.id)
              ORDER BY s.name'
        )->fetchAll();
    }

    /** Active stylists who perform the service. */
    public static function stylistsForService(int $serviceId): array
    {
        $stmt = db()->prepare(
            'SELECT st.id, st.full_name, st.specialty
               FROM stylists st
               JOIN stylist_services ss ON ss.stylist_id = st.id AND ss.service_id = :service
              WHERE st.is_active = 1
              ORDER BY st.full_name'
        );
        $stmt->execute(['service' => $serviceId]);
        return $stmt->fetchAll();
    }

    public static function dailyLimit(): int
    {
        return max(1, (int) setting('daily_appointment_limit'));
    }

    /** Bookings that count toward the daily limit on a date. */
    public static function countOnDate(string $date, int $exceptId = 0): int
    {
        $in = self::placeholders(self::COUNTED);
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM appointments WHERE appt_date = ? AND id <> ? AND status IN ($in)"
        );
        $stmt->execute([$date, $exceptId, ...self::COUNTED]);
        return (int) $stmt->fetchColumn();
    }

    public static function isDateFull(string $date, int $exceptId = 0): bool
    {
        return self::countOnDate($date, $exceptId) >= self::dailyLimit();
    }

    /** True if the stylist already has a pending/confirmed booking overlapping start-end. */
    public static function stylistBusy(int $stylistId, string $date, string $start, string $end, int $exceptId = 0): bool
    {
        $in = self::placeholders(self::BLOCKING);
        $stmt = db()->prepare(
            "SELECT 1 FROM appointments
              WHERE stylist_id = ? AND appt_date = ? AND id <> ?
                AND status IN ($in)
                AND start_time < ? AND end_time > ?
              LIMIT 1"
        );
        $stmt->execute([$stylistId, $date, $exceptId, ...self::BLOCKING, $end, $start]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Free start times for a stylist on a date, e.g. ['09:00', '09:30', ...].
     * Skips times in the past, times that would run past closing, and times that overlap a booking.
     */
    public static function availableSlots(int $stylistId, string $date, int $duration, int $exceptId = 0): array
    {
        if (!self::isValidDate($date) || $date < date('Y-m-d')) {
            return [];
        }

        $in = self::placeholders(self::BLOCKING);
        $stmt = db()->prepare(
            "SELECT start_time, end_time FROM appointments
              WHERE stylist_id = ? AND appt_date = ? AND id <> ? AND status IN ($in)"
        );
        $stmt->execute([$stylistId, $date, $exceptId, ...self::BLOCKING]);
        $booked = array_map(
            static fn (array $b) => [strtotime("$date {$b['start_time']}"), strtotime("$date {$b['end_time']}")],
            $stmt->fetchAll()
        );

        $open     = strtotime("$date " . setting('opening_time'));
        $close    = strtotime("$date " . setting('closing_time'));
        $step     = max(5, (int) setting('slot_interval_minutes')) * 60;
        $length   = $duration * 60;
        $now      = time();

        $slots = [];
        for ($start = $open; $start + $length <= $close; $start += $step) {
            $end = $start + $length;
            if ($start <= $now) {
                continue;
            }
            foreach ($booked as [$bStart, $bEnd]) {
                if ($start < $bEnd && $end > $bStart) {
                    continue 2;
                }
            }
            $slots[] = date('H:i', $start);
        }
        return $slots;
    }

    /**
     * Check a booking before saving it. Returns a list of error messages (empty = OK).
     * $b = ['service_id', 'stylist_id', 'date' (Y-m-d), 'time' (H:i)]
     */
    public static function check(array $b, int $exceptId = 0): array
    {
        $service = self::service((int) $b['service_id']);
        if (!$service) {
            return ['That service is not available.'];
        }
        if (!self::stylistFor((int) $b['stylist_id'], (int) $service['id'])) {
            return ['That stylist is not available for this service.'];
        }
        if (!self::isValidDate($b['date']) || !preg_match('/^\d{2}:\d{2}$/', $b['time'])) {
            return ['Choose a valid date and time.'];
        }

        $start = $b['time'] . ':00';
        $end   = self::endTime($start, (int) $service['duration_minutes']);

        if (strtotime("{$b['date']} $start") <= time()) {
            return ['That time has already passed. Choose a later time.'];
        }
        if ($start < setting('opening_time') . ':00' || $end > setting('closing_time') . ':00' || $end <= $start) {
            return ['Choose a time within opening hours (' . fmt_time(setting('opening_time')) . ' to ' . fmt_time(setting('closing_time')) . ').'];
        }
        if (self::isDateFull($b['date'], $exceptId)) {
            return ['Sorry, the salon is fully booked on ' . fmt_date($b['date']) . '. Please choose another date.'];
        }
        if (self::stylistBusy((int) $b['stylist_id'], $b['date'], $start, $end, $exceptId)) {
            return ['The stylist is not available at that time. Please choose another time.'];
        }

        return [];
    }

    /**
     * Lock so two people can't book the last slot at the same moment.
     * Call inside a transaction, right before check() + INSERT.
     */
    public static function lock(): void
    {
        $pdo = db();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'daily_appointment_limit' FOR UPDATE");
        }
    }

    public static function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    private static function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
