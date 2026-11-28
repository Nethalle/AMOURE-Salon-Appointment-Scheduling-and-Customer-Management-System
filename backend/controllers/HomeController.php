<?php
// Public home page. Everything shown comes from the database or Settings, so nothing is made up.

declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
        // Service categories with how many services and the lowest price ("from ₱250").
        $byCat = [];
        foreach (db()->query('SELECT category, COUNT(*) AS n, MIN(price) AS from_price FROM services WHERE is_active = 1 GROUP BY category')->fetchAll() as $row) {
            $byCat[$row['category']] = $row;
        }
        $services = [];
        foreach (array_keys(SERVICE_CATEGORIES) as $cat) {
            if (isset($byCat[$cat])) {
                $services[] = ['name' => $cat] + $byCat[$cat];
            }
        }

        $stylists = db()->query(
            'SELECT id, full_name, specialty FROM stylists WHERE is_active = 1 ORDER BY id'
        )->fetchAll();

        // Approved reviews shown as customer vouches: one card per customer (their newest review with words,
        // otherwise their newest rating), so a repeat customer doesn't fill the section. Names shortened for privacy.
        $all = db()->query(
            "SELECT r.rating, r.comment, r.created_at, a.customer_id, c.full_name, s.name AS service, st.full_name AS stylist
               FROM reviews r
               JOIN appointments a ON a.id = r.appointment_id
               JOIN customers c    ON c.id = a.customer_id
               JOIN services s     ON s.id = a.service_id
               JOIN stylists st    ON st.id = a.stylist_id
              WHERE r.status = 'approved'
              ORDER BY r.created_at DESC
              LIMIT 200"
        )->fetchAll();
        $byCustomer = [];
        foreach ($all as $r) {
            $id = (int) $r['customer_id'];
            $hasWords = trim((string) $r['comment']) !== '';
            if (!isset($byCustomer[$id])) {
                $byCustomer[$id] = $r + ['reviews' => 0];
            } elseif ($hasWords && trim((string) $byCustomer[$id]['comment']) === '') {
                $byCustomer[$id] = $r + ['reviews' => $byCustomer[$id]['reviews']];
            }
            $byCustomer[$id]['reviews']++;
        }
        // Reviews with words first, then newest.
        uasort($byCustomer, static fn ($x, $y) =>
            [trim((string) $y['comment']) !== '', $y['created_at']] <=> [trim((string) $x['comment']) !== '', $x['created_at']]);
        $reviews = array_slice(array_values($byCustomer), 0, 6);

        $visits = [];
        if ($reviews) {
            $ids  = array_map(static fn ($r) => (int) $r['customer_id'], $reviews);
            $in   = implode(',', array_fill(0, count($ids), '?'));
            $stmt = db()->prepare("SELECT customer_id, COUNT(*) AS n FROM appointments WHERE status = 'completed' AND customer_id IN ($in) GROUP BY customer_id");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $v) { $visits[(int) $v['customer_id']] = (int) $v['n']; }
        }
        foreach ($reviews as &$r) {
            $r['name']    = short_name($r['full_name']);
            $r['visits']  = $visits[(int) $r['customer_id']] ?? 1;
            $r['comment'] = trim((string) $r['comment']) !== '' ? $r['comment'] : null;
        }
        unset($r);

        $rating = db()->query("SELECT COUNT(*) AS n, AVG(rating) AS avg FROM reviews WHERE status = 'approved'")->fetch();

        // Real numbers for the "About" stats.
        $stats = [
            'services'  => (int) db()->query('SELECT COUNT(*) FROM services WHERE is_active = 1')->fetchColumn(),
            'stylists'  => (int) db()->query('SELECT COUNT(*) FROM stylists WHERE is_active = 1')->fetchColumn(),
            'completed' => (int) db()->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn(),
            'rating'    => $rating['n'] ? round((float) $rating['avg'], 1) : null,
        ];

        view('public/home', [
            'title'       => 'Welcome',
            'services'    => $services,
            'stylists'    => $stylists,
            'reviews'     => $reviews,
            'reviewCount' => (int) $rating['n'],
            'avgRating'   => $stats['rating'],
            'stats'       => $stats,
        ]);
    }
}
