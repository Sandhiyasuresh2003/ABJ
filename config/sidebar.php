<?php

return [
    'roles' => [
        'admin' => [
            ['label' => 'Attendance', 'icon' => '◷', 'route' => 'attendance.index', 'active' => 'attendance.*'],
            ['label' => 'Employees', 'icon' => '♙', 'route' => 'employees.index', 'active' => 'employees.*'],
            ['label' => 'Leave Request', 'icon' => '▣', 'route' => 'leaves.index', 'active' => 'leaves.*'],
            ['label' => 'Permissions', 'icon' => '◴', 'route' => 'permissions.index', 'active' => 'permissions.*'],
            ['label' => 'Petty Cash', 'icon' => '₹', 'route' => 'petty-cash.index', 'active' => 'petty-cash.*'],
            ['label' => 'Properties', 'icon' => '⌂', 'route' => 'admin.pending', 'params' => ['module' => 'properties'], 'active' => 'admin.pending'],
            ['label' => 'Recovery codes', 'icon' => '♧', 'route' => 'admin.password-recovery-codes.index', 'active' => 'admin.password-recovery-codes.*'],
        ],
        'employee' => [
            ['label' => 'Attendance', 'icon' => '◷', 'route' => 'employee.attendance', 'active' => 'employee.attendance'],
            ['label' => 'Bookings', 'icon' => '▣', 'route' => 'employee.module', 'params' => ['module' => 'bookings'], 'active' => 'employee.module'],
            ['label' => 'Dashboard', 'icon' => '▦', 'route' => 'employee.workspace', 'active' => 'employee.workspace'],
            ['label' => 'Leads', 'icon' => '◉', 'route' => 'employee.module', 'params' => ['module' => 'leads'], 'active' => 'employee.module'],
            ['label' => 'Leave Request', 'icon' => '▣', 'route' => 'employee.leaves', 'active' => 'employee.leaves'],
            ['label' => 'Permissions', 'icon' => '◴', 'route' => 'employee.permissions', 'active' => 'employee.permissions'],
            ['label' => 'Reports', 'icon' => '▤', 'route' => 'employee.module', 'params' => ['module' => 'reports'], 'active' => 'employee.module'],
        ],
        'owner' => [
            ['label' => 'Attendance', 'icon' => '◷', 'route' => 'attendance.index', 'active' => 'attendance.*'],
            ['label' => 'Bookings', 'icon' => '▣', 'route' => 'admin.pending', 'params' => ['module' => 'bookings'], 'active' => 'admin.pending'],
            ['label' => 'Dashboard', 'icon' => '▦', 'route' => 'dashboard', 'active' => 'dashboard'],
            ['label' => 'Employees', 'icon' => '♙', 'route' => 'employees.index', 'active' => 'employees.*'],
            ['label' => 'Leads', 'icon' => '◉', 'route' => 'admin.pending', 'params' => ['module' => 'leads'], 'active' => 'admin.pending'],
            ['label' => 'Leave Request', 'icon' => '▣', 'route' => 'leaves.index', 'active' => 'leaves.*'],
            ['label' => 'Permissions', 'icon' => '◴', 'route' => 'permissions.index', 'active' => 'permissions.*'],
            ['label' => 'Petty Cash', 'icon' => '₹', 'route' => 'petty-cash.index', 'active' => 'petty-cash.*'],
            ['label' => 'Properties', 'icon' => '⌂', 'route' => 'admin.pending', 'params' => ['module' => 'properties'], 'active' => 'admin.pending'],
            ['label' => 'Recovery codes', 'icon' => '♧', 'route' => 'admin.password-recovery-codes.index', 'active' => 'admin.password-recovery-codes.*'],
            ['label' => 'Reports', 'icon' => '▤', 'route' => 'admin.pending', 'params' => ['module' => 'reports'], 'active' => 'admin.pending'],
            ['label' => 'Settings', 'icon' => '⚙', 'route' => 'admin.pending', 'params' => ['module' => 'settings'], 'active' => 'admin.pending'],
        ],
    ],
];
