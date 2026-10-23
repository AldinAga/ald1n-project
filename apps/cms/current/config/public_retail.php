<?php

declare(strict_types=1);

return [
    // Explicit deployment/merchant approval. No public read, cart or checkout until enabled.
    'enabled' => env('PUBLIC_RETAIL_ENABLED', false),
    // Configured e-mail receipts must have passed sender/queue readiness checks.
    'email_receipt' => env('PUBLIC_RETAIL_EMAIL_RECEIPT', false),
    'access_days' => 30,
    'max_distinct_items' => 20,
    'max_quantity_per_item' => 10,
];
