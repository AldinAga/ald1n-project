<?php

declare(strict_types=1);

// Author: Ald1n <https://ald1n.com>

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Redesign M1: the shell is presentation only. Which links a role sees must not change.
 */
final class AdminShellNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_sees_every_navigation_group_with_exact_current_page_marking(): void
    {
        $html = $this->page($this->userWithRole('superadmin', 'shell-nav-super'), '/');
        $nav = $this->nav($html);

        foreach (['Početna', 'Artikli', 'Porudžbine', 'Administracija', 'Korisnici', 'Podešavanja'] as $label) {
            self::assertStringContainsString('>'.$label, $nav, $label);
        }
        foreach (['admin.users.index', 'admin.settings.index', 'admin.orders.index', 'admin.stock.index'] as $route) {
            self::assertStringContainsString('href="'.route($route).'"', $nav, $route);
        }
        self::assertStringContainsString('data-nav-smtp-settings', $nav);
        self::assertStringContainsString('data-nav-public-storefront', $nav);
        // Exactly one entry is the current page: the dashboard link.
        self::assertSame(1, substr_count($nav, 'aria-current="page"'));
        self::assertMatchesRegularExpression('~<a class="a1-nav__link a1-nav__link--active" href="'.preg_quote(route('dashboard'), '~').'" aria-current="page">Početna</a>~', $nav);
    }

    public function test_restricted_role_does_not_see_admin_only_items_that_the_superadmin_sees(): void
    {
        $html = $this->page($this->userWithRole('user', 'shell-nav-user'), '/');
        $nav = $this->nav($html);

        self::assertStringContainsString('>Početna', $nav);
        foreach (['admin.users.index', 'admin.settings.index', 'admin.settings.smtp.index', 'admin.settings.public-storefront.index', 'admin.user-groups.index'] as $route) {
            self::assertStringNotContainsString('href="'.route($route).'"', $nav, $route);
        }
        foreach (['>Korisnici', '>Podešavanja'] as $label) {
            self::assertStringNotContainsString($label, $nav, $label);
        }
        self::assertStringNotContainsString('data-nav-smtp-settings', $nav);
        self::assertStringNotContainsString('data-nav-public-storefront', $nav);
        // The restricted role still gets the shared shell controls.
        self::assertStringContainsString('data-theme-mode-option="dark"', $html);
        self::assertStringContainsString('action="'.route('logout').'"', $html);
    }

    public function test_section_pages_mark_the_group_active_and_the_exact_item_current(): void
    {
        $html = $this->page($this->userWithRole('superadmin', 'shell-nav-section'), '/admin/users');
        $nav = $this->nav($html);

        self::assertMatchesRegularExpression('~<summary class="a1-nav__link a1-nav__link--active">Korisnici</summary>~', $nav);
        self::assertMatchesRegularExpression('~<a class="a1-nav-dd__item" href="'.preg_quote(route('admin.users.index'), '~').'" aria-current="page">Svi korisnici</a>~', $nav);
        self::assertStringNotContainsString('aria-current="page">Početna', $nav);
    }

    private function userWithRole(string $slug, string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $slug)->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Shell',
            'status' => 'active',
        ]);
    }

    private function page(User $user, string $path): string
    {
        return (string) $this->actingAs($user)->get($path)->assertOk()->getContent();
    }

    private function nav(string $html): string
    {
        $begin = strpos($html, '<nav class="a1-header__nav"');
        $end = strpos($html, '</nav>', $begin ?: 0);
        self::assertNotFalse($begin);
        self::assertNotFalse($end);

        return substr($html, $begin, $end - $begin);
    }
}
