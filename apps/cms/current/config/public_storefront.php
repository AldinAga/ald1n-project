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
    // Unavailable until checkout, after-sales and merchant terms have passed QA.
    'checkout_enabled' => filter_var(env('PUBLIC_CHECKOUT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'terms_url' => env('PUBLIC_CHECKOUT_TERMS_URL'),
    'privacy_url' => env('PUBLIC_CHECKOUT_PRIVACY_URL'),
    'shipping_fee_rsd' => env('PUBLIC_CHECKOUT_SHIPPING_FEE_RSD'),
    'shipping_description' => env('PUBLIC_CHECKOUT_SHIPPING_DESCRIPTION'),
    'payment_methods' => array_filter(explode(',', (string) env('PUBLIC_CHECKOUT_PAYMENT_METHODS', ''))),
    'support_email' => env('PUBLIC_CHECKOUT_SUPPORT_EMAIL'),
    'bank_account_id' => env('PUBLIC_CHECKOUT_BANK_ACCOUNT_ID'),
    'delivery_methods' => array_filter(explode(',', (string) env('PUBLIC_CHECKOUT_DELIVERY_METHODS', 'courier,own_transport,customer_pickup'))),
    'deferred_payment_days' => env('PUBLIC_CHECKOUT_DEFERRED_PAYMENT_DAYS'),
    'deferred_payment_terms' => env('PUBLIC_CHECKOUT_DEFERRED_PAYMENT_TERMS'),
];
