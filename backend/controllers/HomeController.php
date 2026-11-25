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

        // Approved reviews shown as customer vouches. Names shortened to "Jessica R." for privacy.
        $reviews = db()->query(
            "SELECT r.rating, r.comment, c.full_name, s.name AS service
               FROM reviews r
               JOIN appointments a ON a.id = r.appointment_id
               JOIN customers c    ON c.id = a.customer_id
               JOIN services s     ON s.id = a.service_id
              WHERE r.status = 'approved'
              ORDER BY r.created_at DESC
              LIMIT 6"
        )->fetchAll();
        foreach ($reviews as &$r) {
            $r['name'] = short_name($r['full_name']);
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
