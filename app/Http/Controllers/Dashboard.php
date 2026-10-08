<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class Dashboard extends Controller
{
    /**
     * Show the dashboard overview page.
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }
}
