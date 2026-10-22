<?php

declare(strict_types=1);

// Pure URL contract: no Laravel bootstrap, database, network or business writes.
$helper = dirname(__DIR__).'/app/Support/ShipmentTrackingUrl.php';
if (!is_file($helper)) {
    fwrite(STDERR, "FAIL missing production ShipmentTrackingUrl helper\n");
    exit(1);
}
require $helper;
$cases = [
    ['https://carrier.example.test/track?code=ABC-12', 'https://carrier.example.test/track?code=ABC-12'],
    [' https://www.posta.rs/ ', 'https://www.posta.rs/'],
    ['https://carrier.example.test:443/track', 'https://carrier.example.test:443/track'],
    ['javascript:alert(1)', null], ['data:text/html,test', null], ['//carrier.example.test/track', null],
    ['https://user:pass@carrier.example.test/', null], ['http://carrier.example.test/', null],
    ['https://localhost/track', null], ['https://127.0.0.1/', null], ['https://[::1]/', null],
    ['https://10.0.0.1/', null], ['https://internal/track', null], ['https://sub.localhost/', null],
    ["https://carrier.example.test/\ntrack", null], ['https://carrier.example.test/%0atrack', null],
    ['https://carrier.example.test\\@evil.test/', null], ['https://carrier.example.test:8443/', null],
    ['https://0177.0.0.1/', null], ['https://0x7f.0.0.1/', null],
    ['https://carrier.local/', null], ['https://carrier.internal/', null],
    ['', null], [null, null], [str_repeat('x', 2049), null],
];
foreach ($cases as $i => [$input, $expected]) {
    $actual = App\Support\ShipmentTrackingUrl::safe($input);
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAIL URL case '.($i + 1)."\n");
        exit(1);
    }
}
echo 'OPERATOR796_LINKS=PASS CASES='.count($cases)."\n";
