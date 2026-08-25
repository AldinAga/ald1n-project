<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ManagementReportService;
use App\Services\ModuleVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FoundationController extends Controller
{
    /** @var array<string,array{label:string,description:string,permissions:list<string>}> */
    private const MODULES = [
        'orders' => [
            'label' => 'Porudžbine',
            'description' => 'Administracija porudžbina, uplata, dokumenata i isporuke.',
            'permissions' => [
                'orders.manage',
                'orders.reassign',
                'orders.internal_notes',
                'orders.confirm_delivery',
                'orders.reopen',
                'payments.manage',
                'invoices.manage',
            ],
        ],
        'commissions' => [
            'label' => 'Provizije',
            'description' => 'Pregled i obrada administratorskih provizija.',
            'permissions' => ['commissions.manage'],
        ],
        'warranties' => [
            'label' => 'Garancije',
            'description' => 'Pravila, garancije i preventivno održavanje.',
            'permissions' => ['warranties.manage'],
        ],
        'reports' => [
            'label' => 'Izveštaji',
            'description' => 'Operativni i upravljački izveštaji.',
            'permissions' => ['reports.view', 'reports.export', 'reports.manage'],
        ],
        'system_health' => [
            'label' => 'System Health',
            'description' => 'Zdravlje aplikacije, scheduler-a, storage-a i baze.',
            'permissions' => ['system.health'],
        ],
        'audit' => [
            'label' => 'Audit',
            'description' => 'Pregled audit i security događaja.',
            'permissions' => ['catalog.audit', 'audit.export', 'security.view'],
        ],
        'inventory' => [
            'label' => 'Lager',
            'description' => 'Stanje, korekcije, prijem robe i popis.',
            'permissions' => [
                'stock.view',
                'stock.adjust',
                'inventory.receive',
                'inventory.count',
                'inventory.export',
            ],
        ],
        'service_parts' => [
            'label' => 'Servisni delovi',
            'description' => 'Servisni lager i nabavka rezervnih delova.',
            'permissions' => [
                'service_parts.view',
                'service_parts.manage',
                'service_parts.procurement',
            ],
        ],
        'after_sales' => [
            'label' => 'Postprodaja',
            'description' => 'Administracija reklamacija, servisa, povrata i refundacija.',
            'permissions' => ['after_sales.manage', 'after_sales.execute'],
        ],
        'field_operations' => [
            'label' => 'Terenske operacije',
            'description' => 'Radni nalozi, ekipe, raspored i izvršenje na terenu.',
            'permissions' => ['field_operations.view', 'field_operations.manage'],
        ],
        'catalog' => [
            'label' => 'Katalog',
            'description' => 'Artikli, slike, šifarnici i kontrolisana sinhronizacija.',
            'permissions' => [
                'catalog.manage_products',
                'catalog.manage_images',
                'catalog.manage_taxonomy',
                'catalog.sync_legacy',
            ],
        ],
        'receivables' => [
            'label' => 'Potraživanja',
            'description' => 'Aging, opomene, planovi otplate i komunikacija.',
            'permissions' => ['receivables.manage'],
        ],
        'users' => [
            'label' => 'Korisnici i dozvole',
            'description' => 'Korisnici, grupe, RBAC i kategorijski scope.',
            'permissions' => ['system.manage_users'],
        ],
        'settings' => [
            'label' => 'Sistem i podešavanja',
            'description' => 'Podešavanja, automatizacija i backup operacije.',
            'permissions' => [
                'system.manage_settings',
                'automation.manage',
                'backups.manage',
            ],
        ],
    ];

    public function show(
        Request $request,
        ManagementReportService $reports,
        ModuleVisibilityService $moduleVisibility,
    ): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $modules = [];
        $enabledCount = 0;

        foreach (self::MODULES as $key => $definition) {
            $enabled = false;

            if ($moduleVisibility->enabled($key)) {
                foreach ($definition['permissions'] as $permission) {
                    if ($user->can($permission)) {
                        $enabled = true;
                        break;
                    }
                }
            }

            if ($enabled) {
                $enabledCount++;
            }

            $modules[] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'enabled' => $enabled,
                'permissions' => $definition['permissions'],
            ];
        }

        abort_unless($enabledCount > 0, 403);

        // MOBILE_V0_8_SUPERADMIN_INVENTORY_VALUATION_BATCH3
        $inventoryValuation = null;
        if ($user->hasRole('superadmin')) {
            try {
                $inventory = $reports->inventory();
                $inventoryValuation = [
                    'purchase_value_rsd' => (float) ($inventory['purchase_value_rsd'] ?? 0),
                    'sale_value_rsd' => (float) ($inventory['sale_value_rsd'] ?? 0),
                    'expected_profit_rsd' => (float) ($inventory['expected_profit_rsd'] ?? 0),
                    'missing_cost_items' => (int) ($inventory['missing_cost_items'] ?? 0),
                    'missing_cost_total_items' => (int) ($inventory['missing_cost_total_items'] ?? 0),
                    'missing_sale_value_items' => (int) ($inventory['missing_sale_value_items'] ?? 0),
                    'valuation_complete' => (bool) ($inventory['valuation_complete'] ?? false),
                    'eur_rsd_rate' => isset($inventory['eur_rsd_rate']) ? (float) $inventory['eur_rsd_rate'] : null,
                ];
            } catch (\Throwable) {
                $inventoryValuation = null;
            }
        }

        return response()->json([
            'data' => [
                'api_namespace' => '/api/v1/admin',
                'enabled_module_count' => $enabledCount,
                'inventory_valuation' => $inventoryValuation,
                'modules' => $modules,
            ],
        ]);
    }
}
