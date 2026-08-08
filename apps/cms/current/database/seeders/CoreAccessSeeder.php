<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CoreAccessSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            ['id' => 1, 'name' => 'Korisnik', 'slug' => 'user', 'created_at' => now()],
            ['id' => 2, 'name' => 'Administrator', 'slug' => 'admin', 'created_at' => now()],
            ['id' => 3, 'name' => 'SuperAdmin', 'slug' => 'superadmin', 'created_at' => now()],
        ], ['id'], ['name', 'slug']);

        DB::table('user_groups')->upsert([[
            'id' => 1, 'name' => 'Standardni korisnik', 'slug' => 'standardni-korisnik',
            'description' => 'Podrazumevana grupa sa pristupom katalogu.', 'status' => 'active',
            'category_access_mode' => 'all', 'include_uncategorized' => 1, 'sort_order' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]], ['id'], ['name', 'description', 'status', 'category_access_mode', 'include_uncategorized']);

        $permissions = [
            ['id' => 1, 'name' => 'Pregled kataloga', 'slug' => 'catalog.view', 'description' => 'Pregled dozvoljenih artikala.', 'sort_order' => 10],
            ['id' => 2, 'name' => 'Pregled cena', 'slug' => 'catalog.view_prices', 'description' => 'Pregled cena.', 'sort_order' => 20],
            ['id' => 3, 'name' => 'Kreiranje porudžbine', 'slug' => 'orders.create', 'description' => 'Kreiranje porudžbine.', 'sort_order' => 30],
            ['id' => 4, 'name' => 'Pregled svojih porudžbina', 'slug' => 'orders.view_own', 'description' => 'Pregled sopstvenih porudžbina.', 'sort_order' => 40],
            ['id' => 5, 'name' => 'Upravljanje artiklima', 'slug' => 'catalog.manage_products', 'description' => 'Dodavanje, izmena i arhiviranje artikala.', 'sort_order' => 50],
            ['id' => 6, 'name' => 'Upravljanje slikama', 'slug' => 'catalog.manage_images', 'description' => 'Upload, rotacija, redosled i uklanjanje slika.', 'sort_order' => 60],
            ['id' => 7, 'name' => 'Upravljanje kataloškim šifarnicima', 'slug' => 'catalog.manage_taxonomy', 'description' => 'Kategorije, brendovi, linije, tipovi i specifikacije.', 'sort_order' => 70],
            ['id' => 8, 'name' => 'Pregled audit loga', 'slug' => 'catalog.audit', 'description' => 'Pregled istorije administratorskih promena.', 'sort_order' => 80],
            ['id' => 9, 'name' => 'Legacy sinhronizacija', 'slug' => 'catalog.sync_legacy', 'description' => 'Kontrolisana sinhronizacija legacy kataloga.', 'sort_order' => 90],
            ['id' => 10, 'name' => 'Upravljanje porudžbinama', 'slug' => 'orders.manage', 'description' => 'Pregled i upravljanje svim porudžbinama.', 'sort_order' => 100],
            ['id' => 11, 'name' => 'Upravljanje provizijama', 'slug' => 'commissions.manage', 'description' => 'Pregled i obrada provizija.', 'sort_order' => 110],
            ['id' => 12, 'name' => 'Pregled promena lagera', 'slug' => 'stock.view', 'description' => 'Pregled istorije promena zaliha.', 'sort_order' => 120],
            ['id' => 13, 'name' => 'Upravljanje korisnicima', 'slug' => 'system.manage_users', 'description' => 'Korisnici, grupe, dozvole i kategorijski scope.', 'sort_order' => 130],
            ['id' => 14, 'name' => 'Sistemska podešavanja', 'slug' => 'system.manage_settings', 'description' => 'Izgled sajta, kurs i žiro računi.', 'sort_order' => 140],
            ['id' => 15, 'name' => 'Otkazivanje svoje porudžbine', 'slug' => 'orders.cancel_own', 'description' => 'Otkazivanje sopstvene nove porudžbine uz jednokratni povrat lagera.', 'sort_order' => 45],
            ['id' => 16, 'name' => 'Korekcija lagera', 'slug' => 'stock.adjust', 'description' => 'Ručna korekcija lagera sa obaveznim razlogom i audit zapisom.', 'sort_order' => 125],
            ['id' => 17, 'name' => 'Pregled izveštaja', 'slug' => 'reports.view', 'description' => 'Pregled operativnih i prodajnih izveštaja.', 'sort_order' => 150],
            ['id' => 18, 'name' => 'Izvoz izveštaja', 'slug' => 'reports.export', 'description' => 'CSV i PDF izvoz filtriranih porudžbina.', 'sort_order' => 160],
            ['id' => 19, 'name' => 'Upravljanje dokumentima', 'slug' => 'invoices.manage', 'description' => 'Izdavanje i storniranje predračuna i računa.', 'sort_order' => 170],
            ['id' => 20, 'name' => 'Pregled svojih dokumenata', 'slug' => 'invoices.view_own', 'description' => 'PDF dokumenti sopstvenih porudžbina.', 'sort_order' => 46],
            ['id' => 21, 'name' => 'Pregled svojih provizija', 'slug' => 'commissions.view_own', 'description' => 'Praćenje sopstvenih provizija i isplata.', 'sort_order' => 47],
            ['id' => 22, 'name' => 'Ponovna dodela porudžbine', 'slug' => 'orders.reassign', 'description' => 'SuperAdministrator menja odgovorno lice.', 'sort_order' => 104],
            ['id' => 23, 'name' => 'Interne napomene porudžbine', 'slug' => 'orders.internal_notes', 'description' => 'Administratorske napomene nevidljive korisniku.', 'sort_order' => 105],
            ['id' => 24, 'name' => 'Pregled obaveštenja', 'slug' => 'notifications.view', 'description' => 'Pregled poslovnih obaveštenja.', 'sort_order' => 48],
            ['id' => 25, 'name' => 'Upravljanje uplatama', 'slug' => 'payments.manage', 'description' => 'Verifikacija uplata, refundacije i pregled dokaza.', 'sort_order' => 180],
            ['id' => 26, 'name' => 'Slanje potvrde o uplati', 'slug' => 'payments.upload_proof', 'description' => 'Slanje potvrde za sopstvenu porudžbinu.', 'sort_order' => 49],
            ['id' => 27, 'name' => 'Pregled svojih uplata', 'slug' => 'payments.view_own', 'description' => 'Pregled uplata sopstvenih porudžbina.', 'sort_order' => 50],
            ['id' => 28, 'name' => 'Prijem robe', 'slug' => 'inventory.receive', 'description' => 'Kreiranje i knjiženje ulaza robe.', 'sort_order' => 126],
            ['id' => 29, 'name' => 'Popis lagera', 'slug' => 'inventory.count', 'description' => 'Kreiranje i zaključivanje popisa lagera.', 'sort_order' => 127],
            ['id' => 30, 'name' => 'Izvoz lagera i uplata', 'slug' => 'inventory.export', 'description' => 'CSV izvoz lagera, ulaza, popisa i uplata.', 'sort_order' => 128],
            ['id' => 31, 'name' => 'Upravljanje automatizacijom', 'slug' => 'automation.manage', 'description' => 'Podešavanje, ručno pokretanje i pregled operativnih automatizacija.', 'sort_order' => 190],
            ['id' => 32, 'name' => 'System health', 'slug' => 'system.health', 'description' => 'Pregled zdravlja aplikacije, scheduler-a, storage-a i baze.', 'sort_order' => 200],
            ['id' => 33, 'name' => 'Upravljanje backupima', 'slug' => 'backups.manage', 'description' => 'Kreiranje i retention privatnih bezbednosnih kopija.', 'sort_order' => 210],
            ['id' => 34, 'name' => 'Izvoz audit loga', 'slug' => 'audit.export', 'description' => 'CSV izvoz audit i security događaja.', 'sort_order' => 220],
            ['id' => 35, 'name' => 'Pregled security događaja', 'slug' => 'security.view', 'description' => 'Pregled odbijenih i sumnjivih zahteva.', 'sort_order' => 230],
            ['id' => 36, 'name' => 'Potvrda isporuke', 'slug' => 'orders.confirm_delivery', 'description' => 'Evidentiranje primaoca, datuma, načina i dokaza isporuke.', 'sort_order' => 106],
            ['id' => 37, 'name' => 'Ponovno otvaranje porudžbine', 'slug' => 'orders.reopen', 'description' => 'SuperAdministrator ponovo otvara greškom kompletiranu porudžbinu uz obavezan razlog.', 'sort_order' => 107],
            ['id' => 38, 'name' => 'Otvaranje reklamacije', 'slug' => 'after_sales.create', 'description' => 'Otvaranje reklamacije, povrata ili servisnog zahteva.', 'sort_order' => 51],
            ['id' => 39, 'name' => 'Pregled svojih reklamacija', 'slug' => 'after_sales.view_own', 'description' => 'Pregled sopstvenih postprodajnih slučajeva.', 'sort_order' => 52],
            ['id' => 40, 'name' => 'Upravljanje reklamacijama', 'slug' => 'after_sales.manage', 'description' => 'Obrada, dodela i rešavanje reklamacija, povrata i servisnih zahteva.', 'sort_order' => 108],
            ['id' => 41, 'name' => 'Izvršenje postprodajnih radnji', 'slug' => 'after_sales.execute', 'description' => 'Planiranje i izvršenje servisa, zamene, povrata robe i refundacija.', 'sort_order' => 109],
            ['id' => 42, 'name' => 'Pregled terenskih operacija', 'slug' => 'field_operations.view', 'description' => 'Pregled kalendara, radnih naloga i rasporeda terenskih ekipa.', 'sort_order' => 111],
            ['id' => 43, 'name' => 'Upravljanje terenskim operacijama', 'slug' => 'field_operations.manage', 'description' => 'Raspoređivanje ekipa, evidencija dolaska, troškova i završetka radnog naloga.', 'sort_order' => 112],
            ['id' => 44, 'name' => 'Pregled servisnog lagera', 'slug' => 'service_parts.view', 'description' => 'Pregled rezervnih delova, rezervacija i kretanja.', 'sort_order' => 113],
            ['id' => 45, 'name' => 'Upravljanje servisnim lagerom', 'slug' => 'service_parts.manage', 'description' => 'Kreiranje delova, korekcije, rezervacije i utrošak na radnim nalozima.', 'sort_order' => 114],
            ['id' => 46, 'name' => 'Nabavka servisnih delova', 'slug' => 'service_parts.procurement', 'description' => 'Dobavljači, zahtevi za nabavku i prijem rezervnih delova.', 'sort_order' => 115],
            ['id' => 47, 'name' => 'Pregled svojih garancija', 'slug' => 'warranties.view_own', 'description' => 'Pregled garantnih listova, rokova i preventivnog održavanja.', 'sort_order' => 53],
            ['id' => 48, 'name' => 'Upravljanje garancijama', 'slug' => 'warranties.manage', 'description' => 'Pravila garancije, serijski brojevi i preventivno održavanje.', 'sort_order' => 116],
            ['id' => 49, 'name' => 'Upravljanje potraživanjima', 'slug' => 'receivables.manage', 'description' => 'Aging pregled, automatske opomene, planovi otplate i evidencija komunikacije.', 'sort_order' => 117],
            ['id' => 50, 'name' => 'Upravljanje izveštajima', 'slug' => 'reports.manage', 'description' => 'Zakazivanje, slanje i administracija upravljačkih izveštaja.', 'sort_order' => 165],
        ];
        foreach ($permissions as $permission) {
            $slug = $permission['slug'];
            unset($permission['id'], $permission['slug']);

            $query = DB::table('permissions')->where('slug', $slug);
            $values = $this->onlyExistingColumns('permissions', $permission);
            if (Schema::hasColumn('permissions', 'updated_at')) {
                $values['updated_at'] = now();
            }

            if ($query->exists()) {
                if ($values !== []) {
                    $query->update($values);
                }
                continue;
            }

            $insert = $this->onlyExistingColumns('permissions', ['slug' => $slug] + $permission);
            if (Schema::hasColumn('permissions', 'created_at')) {
                $insert['created_at'] = now();
            }
            if (Schema::hasColumn('permissions', 'updated_at')) {
                $insert['updated_at'] = now();
            }
            DB::table('permissions')->insert($insert);
        }

        $standardPermissionIds = DB::table('permissions')
            ->whereIn('slug', [
                'catalog.view',
                'catalog.view_prices',
                'orders.create',
                'orders.view_own',
                'orders.cancel_own',
                'invoices.view_own',
                'commissions.view_own',
                'notifications.view',
                'payments.upload_proof',
                'payments.view_own',
                'after_sales.create',
                'after_sales.view_own',
                'warranties.view_own',
            ])
            ->pluck('id');

        foreach ($standardPermissionIds as $permissionId) {
            $pivot = [
                'group_id' => 1,
                'permission_id' => $permissionId,
            ];
            if (Schema::hasColumn('user_group_permissions', 'created_at')) {
                $pivot['created_at'] = now();
            }
            DB::table('user_group_permissions')->insertOrIgnore($pivot);
        }
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function onlyExistingColumns(string $table, array $values): array
    {
        $filtered = [];
        foreach ($values as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }
        return $filtered;
    }
}
