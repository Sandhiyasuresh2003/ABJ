<?php

return [
    // Single-company installation. Add a tenant key and scope before multi-company use.
    'phone_pattern' => '/^[6-9][0-9]{9}$/',
    'country_code' => '91',
    'max_upload_kb' => 5120,
    'max_rows' => 5000,
    'max_columns' => 64,
    'max_uncompressed_bytes' => 32 * 1024 * 1024,
    'preview_hours' => 24,
    'permissions' => [
        'admin' => ['create', 'import', 'assign', 'status', 'edit', 'followup', 'mobile', 'export', 'history'],
        'owner' => ['mobile', 'import', 'export', 'history'],
        'employee' => ['edit', 'followup'],
    ],
];
