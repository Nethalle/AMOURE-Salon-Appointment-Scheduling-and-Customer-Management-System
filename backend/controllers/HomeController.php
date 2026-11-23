<?php
declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
        view('public/home', ['title' => 'Welcome']);
    }
}
