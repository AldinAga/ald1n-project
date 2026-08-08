<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CmsV215ContractTest extends TestCase
{
    public function test_global_ux_runtime_is_loaded_and_protects_long_forms(): void
    {
        $root = dirname(__DIR__, 2);
        $layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
        $runtime = (string) file_get_contents($root.'/public/assets/js/ux-runtime.js');

        self::assertStringContainsString('assets/js/ux-runtime.js', $layout);
        self::assertStringContainsString('createMobileActionDock', $runtime);
        self::assertStringContainsString('protectForms', $runtime);
        self::assertStringContainsString('protectUnsavedChanges', $runtime);
        self::assertStringContainsString("setAttribute('aria-busy', 'true')", $runtime);
        self::assertStringContainsString("window.addEventListener('beforeunload'", $runtime);
    }

    public function test_mobile_and_accessibility_css_contract_is_present(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2).'/public/assets/css/app.css');

        self::assertStringContainsString('--ux-touch-target:46px', $css);
        self::assertStringContainsString('.ux-mobile-action-dock', $css);
        self::assertStringContainsString('.has-field-error', $css);
        self::assertStringContainsString('prefers-reduced-motion', $css);
    }

    public function test_system_error_pages_are_available(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            self::assertFileExists($root.'/resources/views/errors/'.$status.'.blade.php');
        }
        self::assertFileExists($root.'/resources/views/errors/minimal.blade.php');
    }

    public function test_runtime_doctor_audits_route_controller_actions(): void
    {
        $doctor = (string) file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/CmsV215DoctorCommand.php');

        self::assertStringContainsString('auditRouteActions', $doctor);
        self::assertStringContainsString('class_exists($class)', $doctor);
        self::assertStringContainsString('method_exists($class, $method)', $doctor);
    }

    public function test_desktop_power_supply_migration_uses_existing_dictionary_field(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php');

        self::assertStringContainsString("PRODUCT_TYPE_SLUG = 'desktop-racunar'", $migration);
        self::assertStringContainsString("FIELD_SLUG = 'snaga-napajanja'", $migration);
        self::assertStringContainsString("['status' => 'active']", $migration);
        self::assertStringContainsString('intdiv(count($assignedIds) + 1, 2)', $migration);
        self::assertStringNotContainsString("DB::table('specification_fields')->insert", $migration);
    }

    public function test_stable_release_profile_contains_v215_doctor_with_render_and_repair(): void
    {
        $config = require dirname(__DIR__, 2).'/config/release.php';

        self::assertContains('cms_v215', $config['profiles']['stable']);
        self::assertSame('app:cms-v2-1-5-doctor', $config['checks']['cms_v215']['command']);
        self::assertSame(['--render' => true], $config['checks']['cms_v215']['render']);
        self::assertSame(['--repair' => true], $config['checks']['cms_v215']['repair']);
    }
}
