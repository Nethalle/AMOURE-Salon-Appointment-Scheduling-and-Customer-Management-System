<?php
// Placeholder dashboards: real counts are added with the Dashboard module.

declare(strict_types=1);

class DashboardController
{
    public function admin(): void
    {
        $user = require_role(['admin']);
        view('admin/dashboard', ['title' => 'Admin dashboard', 'user' => $user]);
    }

    public function staff(): void
    {
        $user = require_role(['admin', 'staff']);
        view('staff/dashboard', ['title' => 'Staff dashboard', 'user' => $user]);
    }
}
