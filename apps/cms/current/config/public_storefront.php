<?php
declare(strict_types=1);

/**
 * Deliberately OFF until the complete guest checkout and after-sales flow
 * passes MariaDB, security, merchant-policy and CloudLinux release gates.
 */
return [
    'enabled' => filter_var(env('PUBLIC_STOREFRONT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'max_items' => 25,
    'max_quantity_per_item' => 20,
];
