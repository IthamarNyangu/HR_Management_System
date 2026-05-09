<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $cards = [
            ['label' => 'Total Employees', 'value' => 0],
            ['label' => 'Disciplinary Cases', 'value' => 0],
            ['label' => 'Promotions', 'value' => 0],
            ['label' => 'Relocations', 'value' => 0],
            ['label' => 'Cases Expiring Soon', 'value' => 0],
        ];

        return view('dashboard', compact('cards'));
    }
}
