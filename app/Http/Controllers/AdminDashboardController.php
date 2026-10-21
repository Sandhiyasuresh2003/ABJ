<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // Illustrative sample data only. Replace with database queries when models are ready.
        $stats = [
            ['label' => 'Total employees', 'value' => 48, 'detail' => 'Across all departments', 'icon' => 'users', 'tone' => 'blue'],
            ['label' => 'Present today', 'value' => 39, 'detail' => '81.25% of workforce', 'icon' => 'check', 'tone' => 'green'],
            ['label' => 'Late arrivals', 'value' => 4, 'detail' => 'Needs review', 'icon' => 'clock', 'tone' => 'amber'],
            ['label' => 'Pending approvals', 'value' => 5, 'detail' => '3 leave · 2 permission', 'icon' => 'calendar', 'tone' => 'purple'],
        ];

        $attendance = [
            ['name' => 'Arun Kumar', 'initials' => 'AK', 'department' => 'Sales', 'check_in' => '09:18 AM', 'status' => 'Late', 'tone' => 'amber'],
            ['name' => 'Priya S', 'initials' => 'PS', 'department' => 'Accounts', 'check_in' => '08:54 AM', 'status' => 'Present', 'tone' => 'green'],
            ['name' => 'Karthik R', 'initials' => 'KR', 'department' => 'Site Operations', 'check_in' => '09:06 AM', 'status' => 'Late', 'tone' => 'amber'],
            ['name' => 'Meena Devi', 'initials' => 'MD', 'department' => 'Administration', 'check_in' => '08:48 AM', 'status' => 'Present', 'tone' => 'green'],
        ];

        $requests = [
            ['name' => 'Divya M', 'initials' => 'DM', 'type' => 'Casual leave', 'date' => '22 Oct · 1 day', 'submitted' => 'Today, 09:12 AM', 'tone' => 'blue'],
            ['name' => 'Suresh P', 'initials' => 'SP', 'type' => 'Permission', 'date' => 'Today · 1 hour', 'submitted' => 'Today, 09:26 AM', 'tone' => 'purple'],
            ['name' => 'Nandhini K', 'initials' => 'NK', 'type' => 'Sick leave', 'date' => '23 Oct · 1 day', 'submitted' => 'Yesterday, 04:40 PM', 'tone' => 'blue'],
        ];

        $cash = [
            'balance' => 25000,
            'received' => 40000,
            'spent' => 15000,
            'transactions' => [
                ['description' => 'Site office supplies', 'category' => 'Office supplies', 'date' => '20 Oct 2026', 'amount' => -1850],
                ['description' => 'Petty cash replenishment', 'category' => 'Cash received', 'date' => '19 Oct 2026', 'amount' => 10000],
                ['description' => 'Local site travel', 'category' => 'Travel', 'date' => '18 Oct 2026', 'amount' => -950],
            ],
        ];

        return view('admin.dashboard', compact('stats', 'attendance', 'requests', 'cash'));
    }
}
