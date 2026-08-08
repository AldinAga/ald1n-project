<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AccessControlDoctorCommand extends Command
{
    protected $signature = 'app:access-control-doctor';

    protected $description = 'Proveri role, permission slugove, route zastitu, superadmin nalog i orphan pristupne zapise.';

    public function handle(): int
    {
        $failed = false;
        $requiredTables = ['roles', 'permissions', 'user_groups', 'user_group_permissions', 'users'];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $this->info('PASS '.$table.' je spremna.');
            } else {
                $this->error('FAIL Nedostaje pristupna tabela: '.$table.'.');
                $failed = true;
            }
        }

        if ($failed) {
            return self::FAILURE;
        }

        try {
            $roleSlugs = DB::table('roles')->pluck('slug')->map(static fn (mixed $value): string => (string) $value)->all();
            foreach (['user', 'admin', 'superadmin'] as $requiredRole) {
                if (in_array($requiredRole, $roleSlugs, true)) {
                    $this->info('PASS Uloga postoji: '.$requiredRole.'.');
                } else {
                    $this->error('FAIL Nedostaje obavezna uloga: '.$requiredRole.'.');
                    $failed = true;
                }
            }

            $duplicateRoles = DB::table('roles')->select('slug')->groupBy('slug')->havingRaw('COUNT(*) > 1')->pluck('slug')->all();
            $duplicatePermissions = DB::table('permissions')->select('slug')->groupBy('slug')->havingRaw('COUNT(*) > 1')->pluck('slug')->all();
            $duplicateGroups = DB::table('user_groups')->select('slug')->groupBy('slug')->havingRaw('COUNT(*) > 1')->pluck('slug')->all();
            $failed = $this->reportDuplicates('role', $duplicateRoles) || $failed;
            $failed = $this->reportDuplicates('permission', $duplicatePermissions) || $failed;
            $failed = $this->reportDuplicates('user group', $duplicateGroups) || $failed;

            $superadminCount = DB::table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->where('roles.slug', 'superadmin')
                ->where('users.status', 'active')
                ->count();
            if ($superadminCount < 1) {
                $this->error('FAIL Ne postoji aktivan SuperAdministrator.');
                $failed = true;
            } else {
                $this->info('PASS Aktivni SuperAdministratori: '.$superadminCount.'.');
            }

            $orphanRoleUsers = DB::table('users')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->whereNull('roles.id')
                ->count();
            if ($orphanRoleUsers > 0) {
                $this->error('FAIL Korisnici bez validne uloge: '.$orphanRoleUsers.'.');
                $failed = true;
            } else {
                $this->info('PASS Svi korisnici imaju validnu ulogu.');
            }

            $orphanGroupUsers = DB::table('users')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->leftJoin('user_groups', 'user_groups.id', '=', 'users.user_group_id')
                ->where('users.status', 'active')
                ->where('roles.slug', 'user')
                ->where(static function ($query): void {
                    $query->whereNull('user_groups.id')->orWhere('user_groups.status', '!=', 'active');
                })
                ->count();
            if ($orphanGroupUsers > 0) {
                $this->warn('WARN Aktivni korisnici u user ulozi bez aktivne grupe: '.$orphanGroupUsers.'.');
            } else {
                $this->info('PASS Aktivni korisnici imaju validnu korisnicku grupu.');
            }

            $invalidPivots = DB::table('user_group_permissions as ugp')
                ->leftJoin('user_groups as ug', 'ug.id', '=', 'ugp.group_id')
                ->leftJoin('permissions as p', 'p.id', '=', 'ugp.permission_id')
                ->where(static function ($query): void {
                    $query->whereNull('ug.id')->orWhereNull('p.id');
                })
                ->count();
            if ($invalidPivots > 0) {
                $this->error('FAIL Nevalidni user_group_permissions zapisi: '.$invalidPivots.'.');
                $failed = true;
            } else {
                $this->info('PASS Permission pivot zapisi su referencijalno ispravni.');
            }

            $permissionSlugs = DB::table('permissions')->pluck('slug')->map(static fn (mixed $value): string => (string) $value)->all();
            $routeAudit = $this->auditRoutes($permissionSlugs);
            foreach ($routeAudit['missing_permissions'] as $slug) {
                $this->error('FAIL Route koristi permission koji ne postoji u bazi: '.$slug.'.');
                $failed = true;
            }
            if ($routeAudit['missing_permissions'] === []) {
                $this->info('PASS Svi route permission slugovi postoje u bazi.');
            }

            foreach ($routeAudit['unprotected_admin'] as $route) {
                $this->error('FAIL Admin ruta nema auth/active zastitu: '.$route.'.');
                $failed = true;
            }
            if ($routeAudit['unprotected_admin'] === []) {
                $this->info('PASS Sve admin rute imaju auth i active middleware.');
            }

            foreach ($routeAudit['unthrottled_sensitive'] as $route) {
                $this->warn('WARN Osetljiva javna ruta nema throttle middleware: '.$route.'.');
            }
            if ($routeAudit['unthrottled_sensitive'] === []) {
                $this->info('PASS Login, reset i activation write rute imaju throttle zastitu.');
            }
        } catch (Throwable $exception) {
            $this->error('FAIL Access control audit nije uspeo: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->error('Access control provera nije prosla.');

            return self::FAILURE;
        }

        $this->info('Access control je spreman.');

        return self::SUCCESS;
    }

    /** @param array<int,mixed> $values */
    private function reportDuplicates(string $label, array $values): bool
    {
        if ($values === []) {
            $this->info('PASS Nema dupliranih '.$label.' slugova.');

            return false;
        }

        foreach ($values as $value) {
            $this->error('FAIL Dupliran '.$label.' slug: '.(string) $value.'.');
        }

        return true;
    }

    /**
     * @param list<string> $knownPermissions
     * @return array{missing_permissions:list<string>,unprotected_admin:list<string>,unthrottled_sensitive:list<string>}
     */
    private function auditRoutes(array $knownPermissions): array
    {
        $missing = [];
        $unprotectedAdmin = [];
        $unthrottledSensitive = [];

        foreach (Route::getRoutes() as $route) {
            if (!$route instanceof LaravelRoute) {
                continue;
            }

            $middleware = array_values(array_unique(array_map('strval', $route->gatherMiddleware())));
            $uri = ltrim((string) $route->uri(), '/');
            $name = (string) ($route->getName() ?? $uri);

            foreach ($middleware as $entry) {
                if (!str_starts_with($entry, 'permission:')) {
                    continue;
                }
                $slug = trim(substr($entry, strlen('permission:')));
                if ($slug !== '' && !in_array($slug, $knownPermissions, true)) {
                    $missing[] = $slug;
                }
            }

            if (str_starts_with($uri, 'admin/')) {
                if (!in_array('auth', $middleware, true) || !in_array('active', $middleware, true)) {
                    $unprotectedAdmin[] = $name.' ['.$uri.']';
                }
            }

            $methods = $route->methods();
            $isWrite = array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']) !== [];
            $isSensitivePublic = $isWrite && in_array($uri, ['login', 'forgot-password', 'reset-password', 'activate-account'], true);
            if ($isSensitivePublic && !$this->hasThrottle($middleware)) {
                $unthrottledSensitive[] = $name.' ['.$uri.']';
            }
        }

        sort($missing);
        sort($unprotectedAdmin);
        sort($unthrottledSensitive);

        return [
            'missing_permissions' => array_values(array_unique($missing)),
            'unprotected_admin' => array_values(array_unique($unprotectedAdmin)),
            'unthrottled_sensitive' => array_values(array_unique($unthrottledSensitive)),
        ];
    }

    /** @param list<string> $middleware */
    private function hasThrottle(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            if (str_starts_with($entry, 'throttle:')) {
                return true;
            }
        }

        return false;
    }
}
