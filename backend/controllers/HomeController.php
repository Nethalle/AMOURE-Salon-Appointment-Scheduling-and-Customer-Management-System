<?php
declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
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

        $stats = db()->query("SELECT COUNT(*) AS n, AVG(rating) AS avg FROM reviews WHERE status = 'approved'")->fetch();

        view('public/home', [
            'title'       => 'Welcome',
            'reviews'     => $reviews,
            'reviewCount' => (int) $stats['n'],
            'avgRating'   => $stats['n'] ? round((float) $stats['avg'], 1) : null,
        ]);
    }
}
