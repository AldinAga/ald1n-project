<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Opens every GET page that needs no URL parameter, on an empty database, as each kind of signed-in user and as a
 * visitor. A page must not answer 500 and must not print raw Blade or PHP (the empty state of /warranties once did).
 * Author: Ald1n <https://ald1n.com>
 */
final class RouteRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** Raw Blade or PHP that must never be visible in a page. */
    private const RAW = '/(?<![@\w])@(?:if|elseif|else|endif|foreach|endforeach|forelse|empty|endforelse|section|endsection|php|endphp|include|extends|can|endcan|isset|unless|csrf)\b|\{\{|\{!!|<\?php|\$errors->|Undefined variable|Undefined array key/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    /** @return list<string> */
    private static function pages(): array
    {
        $uris = [];
        foreach (Route::getRoutes() as $route) {
            $uri = '/'.ltrim($route->uri(), '/');
            if (!in_array('GET', $route->methods(), true) || str_contains($uri, '{') || str_starts_with($uri, '/api/')) {
                continue;
            }
            if (in_array($uri, ['/up', '/logout', '/auth/google', '/auth/google/callback'], true)) {
                continue;
            }
            $uris[] = $uri;
        }
        $uris = array_values(array_unique($uris));
        sort($uris);

        return $uris;
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'smoke-'.$role,
            'email' => 'smoke-'.$role.'@example.test',
            'first_name' => 'Smoke',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    /** @return list<string> */
    private function failuresFor(?User $user): array
    {
        $failures = [];
        foreach (self::pages() as $uri) {
            $client = $user === null ? $this : $this->actingAs($user);
            $response = $client->get($uri);
            $status = $response->getStatusCode();
            $body = $status >= 300 && $status < 400 ? '' : (string) $response->getContent();
            if ($status >= 500) {
                $failures[] = "$uri answered $status";
            } elseif (str_contains((string) $response->headers->get('Content-Type'), 'text/html') && preg_match(self::RAW, $body, $m, PREG_OFFSET_CAPTURE)) {
                $failures[] = "$uri prints raw template text near \"".trim(substr($body, max(0, $m[0][1] - 30), 70)).'"';
            }
        }

        return $failures;
    }

    public function test_the_page_list_is_not_empty(): void
    {
        $this->assertGreaterThan(30, count(self::pages()));
    }

    public function test_pages_render_for_a_visitor(): void
    {
        $failures = $this->failuresFor(null);
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_pages_render_for_a_customer(): void
    {
        $failures = $this->failuresFor($this->user('user'));
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_pages_render_for_an_administrator(): void
    {
        $failures = $this->failuresFor($this->user('admin'));
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_pages_render_for_a_super_administrator(): void
    {
        $failures = $this->failuresFor($this->user('superadmin'));
        $this->assertSame([], $failures, implode("\n", $failures));
    }
}
