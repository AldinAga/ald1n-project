<?php

declare(strict_types=1);

namespace App\Services;

final class ModuleVisibilityService
{
    /** @var array<string,array{label:string,description:string}> */
    private const DEFINITIONS = [
        'commissions' => ['label' => 'Provizije', 'description' => 'Administracija i korisnički prikaz provizija.'],
        'after_sales' => ['label' => 'Reklamacije i servisi', 'description' => 'Postprodajni slučajevi, poruke i izvršne radnje.'],
        'field_operations' => ['label' => 'Terenske operacije', 'description' => 'Radni nalozi, rasporedi i terenske ekipe.'],
        'service_parts' => ['label' => 'Servisni lager', 'description' => 'Servisni delovi, rezervacije i nabavka delova.'],
        'warranties' => ['label' => 'Garancije', 'description' => 'Garancije, pravila i preventivno održavanje.'],
        'receivables' => ['label' => 'Potraživanja', 'description' => 'Naplata, planovi, kontakti i podsetnici.'],
        'reports' => ['label' => 'Izveštaji', 'description' => 'Upravljački izveštaji, izvozi i rasporedi.'],
        'inventory' => ['label' => 'Napredni lager', 'description' => 'Ulazi robe, popisi i napredni lager workspace.'],
        'automation' => ['label' => 'Automatizacija', 'description' => 'Operativna upozorenja i automatizovane provere.'],
        'system_health' => ['label' => 'System Health', 'description' => 'Zdravlje sistema, backup i operativne provere.'],
        'audit' => ['label' => 'Audit i bezbednost', 'description' => 'Audit log i bezbednosni pregled.'],
        'customer_portal' => ['label' => 'Customer Portal', 'description' => 'Aktivacije kupaca, portal komunikacija i sesije.'],
        'notifications' => ['label' => 'Obaveštenja', 'description' => 'In-app obaveštenja i korisničke preference.'],
    ];

    /** @var array<string,bool>|null */
    private ?array $resolvedStates = null;

    public function __construct(private readonly SettingsService $settings)
    {
    }

    /** @return array<string,array{label:string,description:string}> */
    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    /** @return array<string,bool> */
    public function states(): array
    {
        if ($this->resolvedStates !== null) {
            return $this->resolvedStates;
        }

        $states = [];
        foreach (array_keys(self::DEFINITIONS) as $module) {
            $stored = strtolower(trim((string) $this->settings->get($this->settingKey($module), '1')));
            $states[$module] = !in_array($stored, ['0', 'false', 'off', 'no', 'disabled'], true);
        }

        return $this->resolvedStates = $states;
    }

    public function enabled(string $module): bool
    {
        if (!array_key_exists($module, self::DEFINITIONS)) {
            return true;
        }

        return $this->states()[$module];
    }

    /** @return array<int,array{key:string,label:string,description:string,enabled:bool}> */
    public function rows(): array
    {
        $states = $this->states();
        $rows = [];
        foreach (self::DEFINITIONS as $key => $definition) {
            $rows[] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'enabled' => $states[$key],
            ];
        }

        return $rows;
    }

    /** @param array<string,bool> $states
     *  @return array<string,bool>
     */
    public function update(array $states, int $userId): array
    {
        $values = [];
        foreach (array_keys(self::DEFINITIONS) as $module) {
            $values[$this->settingKey($module)] = ($states[$module] ?? false) ? '1' : '0';
        }

        $this->settings->putMany($values, $userId);
        $this->resolvedStates = null;

        return $this->states();
    }

    private function settingKey(string $module): string
    {
        return 'module_enabled_'.$module;
    }
}
