#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};
$source = static fn (string $path): string => (string) @file_get_contents($root.'/'.$path);

$settings = $source('app/Services/SettingsService.php');
$service = $source('app/Services/ProductAnnouncementService.php');
$controller = $source('app/Http/Controllers/Admin/ProductController.php');
$settingsController = $source('app/Http/Controllers/Admin/OrderEmailSettingsController.php');
$settingsView = $source('resources/views/admin/settings/order-emails.blade.php');
$mailView = $source('resources/views/emails/order-events.blade.php');
$dispatcher = $source('app/Services/OrderEmailDispatcher.php');
$doctor = $source('app/Console/Commands/OrderEmailsDoctorCommand.php');
$layout = $source('resources/views/layouts/app.blade.php');

$check('Podešavanje je podrazumevano isključeno', str_contains($settings, "'product_email_new_items_enabled' => '0'"));
$check('Podešavanje ima interval slanja', str_contains($settings, "'product_email_new_items_interval_minutes' => '0'"));
$check('Servis koristi postojeći pouzdani outbox', str_contains($service, 'OrderEmailOutbox') && str_contains($service, "'event_type' => 'product_published'"));
$check('Servis šalje samo aktivan objavljen artikal', str_contains($service, "\$product->status !== 'active'") && str_contains($service, '$product->deleted_at !== null'));
$check('Primaoci su aktivni registrovani korisnici', str_contains($service, "where('status', 'active')") && str_contains($service, "whereNotNull('email')"));
$check('Neispravne e-mail adrese se preskaču', str_contains($service, 'FILTER_VALIDATE_EMAIL'));
$check('Slanje je deduplikovano po artiklu i korisniku', str_contains($service, "'product_published|'.\$product->id.'|'.\$recipient->id") && str_contains($service, 'firstOrCreate'));
$check('Outbox poruka ne zavisi od porudžbine', str_contains($service, "'order_id' => null") && str_contains($service, "'document_id' => null"));
$check('Poruka sadrži siguran link ka katalogu', str_contains($service, "route('catalog.show'") && str_contains($service, "'action_url' => \$actionUrl"));
$check('Greška obaveštenja ne poništava artikal', str_contains($service, 'catch (Throwable $exception)') && str_contains($service, 'Log::error'));
$check('Kreiranje aktivnog artikla pokreće najavu', str_contains($controller, 'queueForNewlyPublished($product'));
$check('Izmena pokreće najavu samo pri prvom prelasku u aktivan status', str_contains($controller, '$wasActive') && str_contains($controller, '!$wasActive') && str_contains($controller, "\$updated->status === 'active'"));
$check('JSON odgovor vraća broj pripremljenih obaveštenja', substr_count($controller, "'announcement_count' => \$announcementCount") === 2);
$check('Glavna mail podešavanja imaju checkbox za nove artikle', str_contains($settingsView, 'product_email_new_items_enabled') && str_contains($settingsView, 'sve aktivne registrovane korisnike'));
$check('Glavna mail podešavanja imaju interval novih artikala', str_contains($settingsView, 'product_email_new_items_interval_minutes'));
$check('Controller čuva checkbox i interval', str_contains($settingsController, "'product_email_new_items_enabled'") && str_contains($settingsController, "'product_email_new_items_interval_minutes'"));
$check('E-mail šablon ima tekst i dugme za artikal', str_contains($mailView, 'Novi artikal u katalogu') && str_contains($mailView, 'Pogledaj artikal'));
$check('E-mail najava uključuje glavnu sliku opis i cenu', str_contains($service, "'product_image_url'") && str_contains($service, "'product_description'") && str_contains($service, "'product_price_formatted'") && str_contains($mailView, '<img src="{{ $productImage }}"') && str_contains($mailView, 'Cena: {{ $productPrice }}'));
$check('Dispatcher podržava grupu proizvoda', str_contains($dispatcher, 'Novi artikli u katalogu') && str_contains($dispatcher, "str_starts_with((string) \$row->event_type, 'product_')"));
$check('Doctor proverava novu funkcionalnost', str_contains($doctor, 'ProductAnnouncementService') && str_contains($doctor, 'obaveštenja o novim artiklima'));
$check('Navigacija koristi generički naziv mail podešavanja', str_contains($layout, 'E-mail obaveštenja') && !str_contains($layout, 'E-mail porudžbina'));
$check('Nema nove migracije za obaveštenja', (glob($root.'/database/migrations/*2_1_2*.php') ?: []) === []);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Product announcement smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
