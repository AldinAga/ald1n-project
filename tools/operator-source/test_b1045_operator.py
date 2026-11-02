from __future__ import annotations
import hashlib, pathlib, re, subprocess, tempfile

P = pathlib.Path(__file__).with_name('B1045-ALD1N-M10-ONE-TIME-SMTP-OPERATOR-PROBE.sh')
source = P.read_text(encoding='utf-8')
checks = []

def check(name, test):
    if not test:
        raise SystemExit('FAIL: ' + name)
    print('PASS:',name)
    checks.append(name)

def run(*args, script=P):
    return subprocess.run(['bash',str(script),*args],capture_output=True,text=True,timeout=8)

check('bash syntax', subprocess.run(['bash','-n',str(P)]).returncode == 0)
check('self pin normalized SHA256 matches', hashlib.sha256(re.sub(r"^SELF_NORM_SHA256='[a-f0-9]{64}'$", "SELF_NORM_SHA256='__PIN__'", source, flags=re.M).encode('utf8')).hexdigest() == re.search(r"^SELF_NORM_SHA256='([a-f0-9]{64})'$", source, flags=re.M).group(1))
help_run = run('--help')
check('correct --help runs once with verified self pin', help_run.returncode == 0 and 'one-time warranty operator SMTP probe' in help_run.stdout)
check('non-operator read-only mode fails before PHP/app', run('--preflight').returncode == 2 and 'HOLD_WRONG_HOST_ACCOUNT' in run('--preflight').stderr)
check('non-operator send mode fails before send', run('--probe-alert').returncode == 2 and 'HOLD_WRONG_HOST_ACCOUNT' in run('--probe-alert').stderr)
check('unknown mode rejects', run('--execute').returncode == 2 and 'HOLD_UNKNOWN_MODE' in run('--execute').stderr)
check('missing/extra arguments reject', run().returncode == 2 and run('--help','--probe-alert').returncode == 2)
with tempfile.TemporaryDirectory() as td:
    q=pathlib.Path(td)/P.name
    q.write_text(source.replace('at most ONE synthetic test email','at most TWO synthetic test emails'),encoding='utf8')
    check('tampered script rejected before help', run('--help',script=q).returncode == 2 and 'HOLD_SCRIPT_TAMPERED' in run('--help',script=q).stderr)
start=source.index("cat > \"$TMP_DIR/runner.php\" <<'PHP'\n") + len("cat > \"$TMP_DIR/runner.php\" <<'PHP'\n")
end=source.index('\nPHP\n',start)
php = source[start:end]+'\n'
with tempfile.TemporaryDirectory() as td:
    f=pathlib.Path(td)/'runner.php'; f.write_text(php)
    check('real PHP8.4 lint on embedded Laravel bootstrapping code', subprocess.run(['php','-l',str(f)],capture_output=True).returncode == 0)
check('B962 exact command/config blob pins intact', "EXPECTED_COMMAND_BLOB='7b8568f6fc548cfff9a0046f9564bb10561eefd6'" in source and "EXPECTED_CONFIG_BLOB='7701ccdb958a59adcd78f41843f478d29fa8e2df'" in source)
check('only one Artisan probe call, no execute', php.count("Artisan::call('app:warranty-activation-recover', ['--probe-alert' => true])") == 1 and "'--execute'" not in php and "'--alert'" not in php)
check('user email piped to PHP stdin, no argv or report recipient', 'printf \'%s\\n\' "$OPERATOR_EMAIL" | php' in source and "RECIPIENT=PRIVATE_NOT_RECORDED" in source and "fgets(STDIN)" in php)
check('one-shot durable atomic intent marker and no retries', 'mkdir -m 700 -- "$INTENT_DIR"' in source and 'ONE_TIME_PROBE_INTENT_ALREADY_EXISTS_DO_NOT_RETRY' in source and 'NO_AUTOMATIC_RETRY' in source)
check('actual SMTP required; fake and known Mailpit ports rejected', "(string) config('mail.default') !== 'smtp'" in php and "'mailpit'" in php and '$port === 1025' in php)
check('temporary in-memory config only, .env/htaccess hash checked', "config()->set('warranty_recovery.alerts_enabled', true);" in php and "config()->set('warranty_recovery.alert_email', $recipient);" in php and 'PROTECT_ENV_BEFORE=' in source and 'PROTECT_HT_BEFORE=' in source)
check('dangerous process substitution, /dev/fd, git mutation absent',all(s not in source for s in ('<(','>(','/dev/fd','git pull','git reset','git checkout','git clean','eas build','--auto-submit')))
check('strict user consent and inbox status not assumed', 'SEND ONE B1045 TEST MAIL' in source and 'ACTUAL_INBOX_DELIVERY=OWNER_CONFIRMATION_REQUIRED' in source)
check('single report without customer identifiers or private email', 'MAIL_CONTENT=FIXED_SYNTHETIC_TEST_NO_ORDERS_CUSTOMERS_TOKENS' in source and 'printf %s\n "$OPERATOR_EMAIL" > "$REPORT"' not in source)
print(f'PASS_B1045_LOCAL_QA={len(checks)}_OF_{len(checks)}')
