"""CI-only PHP 8.4 proof; run: python3 startup-cache-fixture.py.

Requires Linux GitHub Actions, PHP 8.4 CLI with Zend OPcache, and RUNNER_TEMP.
Exercises real stale main/dependency bytecode, never reset/invalidation. A fresh
CLI server does NOT share the warm server's SHM. This does not attest LiteSpeed
request overrides or bypass of an existing worker's shared-memory cache.
Sources: https://www.php.net/manual/en/opcache.configuration.php
https://github.com/php/php-src/blob/PHP-8.4/ext/opcache/ZendAccelerator.c
"""

import contextlib
import hashlib
import json
import os
import pathlib
import socket
import subprocess
import tempfile
import time
import urllib.request


def check(condition, detail):
    if not condition:
        raise RuntimeError(detail)


def options(cache, enabled, disk_only=False):
    settings = {
        'opcache.enable': int(enabled), 'opcache.enable_cli': 1,
        'opcache.validate_timestamps': 0, 'opcache.file_update_protection': 0,
        'opcache.file_cache': str(cache), 'opcache.file_cache_only': int(disk_only),
        'opcache.jit': 'off', 'opcache.restrict_api': '',
    }
    return ['php'] + [part for key, value in settings.items()
                      for part in ('-d', f'{key}={value}')]


def write_source(root, marker):
    (root / 'dep.php').write_text("<?php return 'dep-" + marker + "';\n")
    (root / 'main.php').write_text("""<?php
$dep = require __DIR__ . '/dep.php';
echo json_encode([
    'main' => 'main-MARKER', 'dep' => $dep, 'version' => PHP_VERSION,
    'pid' => getmypid(), 'enabled' => (bool) ini_get('opcache.enable'),
    'timestamps' => (int) ini_get('opcache.validate_timestamps'),
    'mainCached' => opcache_is_script_cached(__FILE__),
    'depCached' => opcache_is_script_cached(__DIR__ . '/dep.php'),
]);
""".replace('MARKER', marker))


def expect(value, marker, enabled):
    check(value['version'].startswith('8.4.'), ('PHP_8_4_REQUIRED', value))
    check(value['main'] == 'main-' + marker and value['dep'] == 'dep-' + marker,
          ('MAIN_AND_DEPENDENCY_MUST_MATCH', marker, value))
    check(value['enabled'] is enabled and value['timestamps'] == 0,
          ('EFFECTIVE_STARTUP_SETTINGS', value))


def cache_snapshot(cache):
    return {str(p.relative_to(cache)): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in cache.rglob('*.bin')}


@contextlib.contextmanager
def server(root, cache, enabled):
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    with (root / ('server-on.log' if enabled else 'server-off.log')).open('wb') as log:
        process = subprocess.Popen(options(cache, enabled) +
                                   ['-S', f'127.0.0.1:{port}', '-t', str(root)],
                                   stdout=log, stderr=log)
        opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))

        def request():
            with opener.open(f'http://127.0.0.1:{port}/main.php', timeout=5) as response:
                return json.loads(response.read())

        try:
            for _ in range(100):
                check(process.poll() is None, ('PHP_SERVER_EXITED', process.returncode))
                try:
                    with socket.create_connection(('127.0.0.1', port), timeout=.1):
                        break
                except OSError:
                    time.sleep(.05)
            else:
                raise RuntimeError('PHP_SERVER_START_TIMEOUT')
            yield request
        finally:
            if process.poll() is None:
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait(timeout=5)


def main():
    check(os.environ.get('GITHUB_ACTIONS') == 'true' and os.name == 'posix',
          'CI_ONLY_LINUX_GITHUB_ACTIONS_REQUIRED; do not run on hosting/Windows')
    with tempfile.TemporaryDirectory(prefix='b1143-startup-cache-',
                                     dir=os.environ['RUNNER_TEMP']) as temporary:
        base = pathlib.Path(temporary)
        for mode in ('memory-and-disk', 'disk-only-cross-process'):
            root = base / mode
            root.mkdir()
            cache = root / 'cache'
            cache.mkdir()
            write_source(root, 'old')

            if mode == 'memory-and-disk':
                with server(root, cache, True) as warm:
                    expect(warm(), 'old', True)
                    initial = warm()
                    check(initial['mainCached'] and initial['depCached'],
                          ('BOTH_FILES_MUST_BE_MEMORY_CACHED', initial))
                    before = cache_snapshot(cache)
                    check(any(p.endswith('/main.php.bin') for p in before) and
                          any(p.endswith('/dep.php.bin') for p in before),
                          ('BOTH_FILES_MUST_HAVE_DISK_CACHE', before))
                    write_source(root, 'new')
                    stale = warm()
                    expect(stale, 'old', True)
                    check(stale['pid'] == initial['pid'], 'WARM_PROCESS_CHANGED')
                    with server(root, cache, False) as off:
                        fresh = off()
                        expect(fresh, 'new', False)
                        check(fresh['pid'] != stale['pid'], 'SEPARATE_PROCESS_REQUIRED')
                    expect(warm(), 'old', True)
                    check(cache_snapshot(cache) == before, 'DISK_CACHE_CHANGED')
            else:
                def execute(enabled):
                    result = subprocess.run(options(cache, enabled, True) +
                                            [str(root / 'main.php')],
                                            capture_output=True, text=True, timeout=15)
                    check(result.returncode == 0, ('PHP_CLI_FAILURE', result.stderr))
                    return json.loads(result.stdout)

                expect(execute(True), 'old', True)
                before = cache_snapshot(cache)
                check(any(p.endswith('/main.php.bin') for p in before) and
                      any(p.endswith('/dep.php.bin') for p in before),
                      ('BOTH_FILES_MUST_HAVE_DISK_CACHE', before))
                write_source(root, 'new')
                # Negative control: omitting startup-off must execute stale bytecode.
                expect(execute(True), 'old', True)
                expect(execute(False), 'new', False)
                expect(execute(True), 'old', True)
                check(cache_snapshot(cache) == before, 'DISK_CACHE_CHANGED')
            print(json.dumps({'case': mode, 'result': 'PASS',
                              'staleMainAndDependency': True,
                              'startupOffFreshMainAndDependency': True,
                              'negativeIgnoredStartupOffStaysStale': True,
                              'cacheResetOrInvalidateCalled': False}), flush=True)
        print(json.dumps({'scope': 'actual PHP8.4 CLI-server memory staleness and '
                          'persistent disk-cache bypass by startup opcache.enable=0',
                          'existingWorkerSharedMemoryBypassProven': False,
                          'liteSpeedOverrideEfficacyProven': False,
                          'productionExecution': False}), flush=True)


if __name__ == '__main__':
    main()
