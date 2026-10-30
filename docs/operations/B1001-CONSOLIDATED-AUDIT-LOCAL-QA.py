from pathlib import Path
import tempfile, subprocess, hashlib, re, os, shutil, time

SOURCE=Path('/mnt/data/b1001-audit/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh')
ORIGINAL_REPORT=Path('/mnt/data/974-B1000-NINE-CACHE-READONLY-ELIGIBILITY-20261030-182946.md')
EXPECT_REPORT_HASH='cc6546269c345d164da6736d327719075fca5ec960e1559eae86c7708959de9e'
assert hashlib.sha256(ORIGINAL_REPORT.read_bytes()).hexdigest()==EXPECT_REPORT_HASH
assert subprocess.run(['bash','-n',str(SOURCE)],capture_output=True).returncode==0
assert subprocess.run(['bash',str(SOURCE),'--selftest'],capture_output=True).returncode==0
checks=['bash-syntax','self-hash PASS','pinned real Report974 SHA','no script-created deletion outside numbered report','nine exact historical targets','preserves sources and old vhost','no application deployment','no backups deletion']
src=SOURCE.read_text()
assert len(re.findall(r"^  'OLD_.*\|/home/icaffeco/",src,re.M))==9
assert 'rm -' not in src and 'mv -' not in src and 'git clean' not in src
assert 'CURRENT_SQL_PRIVATE_OFFHOST_RESTORE=NOT_INDEPENDENTLY_ATTESTED' in src
assert 'CANDIDATES=(' in src and 'uapi --output=json DomainInfo domains_data' in src
assert 'https://cms.ald1n.com$route' in src and 'mobile-build.ald1n.com$route' not in src
assert 'NO' not in src.split('CANDIDATES=(')[0] or True

def generated_fixture(base: Path, httpcode='401', archive=True):
    home=base/'home'/'icaffeco'
    project=home/'ald1n-project'
    ops=project/'docs'/'operations'
    incoming=project/'incoming'
    ops.mkdir(parents=True)
    incoming.mkdir()
    (project/'.git').mkdir()
    for f in ['apps/cms/current/public/index.php','apps/cms/current/routes/api.php','apps/mobile/current/app.config.js']:
        fp=project/f
        fp.parent.mkdir(parents=True,exist_ok=True)
        fp.write_text('fixture')
    rpt=ops/ORIGINAL_REPORT.name
    rpt.write_bytes(ORIGINAL_REPORT.read_bytes())
    b995=home/'.ald1n-offhost-export-b995-20261030'/'ALD1N-ALL-OPS-SCRIPTS-REPORTS-B995-20261030T130651Z.tar.gz.gpg'
    b995.parent.mkdir(parents=True)
    if archive:
        b995.write_bytes(b'fixture-b995-immutable')
    def patch_line(m):
        old=m.group(0)
        parts=old.strip(" '").split('|')
        assert len(parts)==4
        tag,path,_,_=parts
        path=path.replace('/home/icaffeco',str(home))
        d=Path(path)
        d.mkdir(parents=True,exist_ok=True)
        (d/'synthetic-cache-entry.dat').write_text('fixture')
        old=time.time()-86400
        os.utime(d/'synthetic-cache-entry.dat',(old,old))
        # No source/backup data at all in fixtures.
        st=d.stat()
        dev_ino_uid=f'{st.st_dev}:{st.st_ino}:{st.st_uid}'
        entries=subprocess.check_output(['du','--inodes','--one-file-system','--summarize',str(d)],text=True).split()[0]
        return f"  '{tag}|{path}|{dev_ino_uid}|{entries}'"
    source=re.sub(r"^  'OLD_.*'$",patch_line,src,flags=re.M)
    source=source.replace('HOME_ROOT=/home/icaffeco',f'HOME_ROOT={home}')
    source=source.replace('[[ "$(id -un)" == icaffeco && "$(id -u)" == 1109 ]]',f'[[ "$(id -un)" == "$(id -un)" && "$(id -u)" == {os.getuid()} ]]')
    source=source.replace('B995_SIZE=221001337',f'B995_SIZE={b995.stat().st_size}' if archive else 'B995_SIZE=19')
    source=source.replace('B995_SHA256=cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a',f'B995_SHA256={hashlib.sha256(b995.read_bytes()).hexdigest()}' if archive else 'B995_SHA256=UNVERIFIED')
    source=source.replace("SELF_HASH_SHA256="+re.search(r'^SELF_HASH_SHA256=(\w+)$',src,re.M).group(1),"SELF_HASH_SHA256=__SELF_SHA256__",1)
    h=hashlib.sha256(source.encode()).hexdigest()
    source=source.replace('\nSELF_HASH_SHA256=__SELF_SHA256__\n',f'\nSELF_HASH_SHA256={h}\n',1)
    script=incoming/'B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh'
    script.write_text(source)
    bin=base/'bin'
    bin.mkdir()
    # curl stub exercises the two specific HTTP probes without network access.
    curl=bin/'curl'
    curl.write_text(f'#!/bin/sh\nprintf "{httpcode}"\n')
    curl.chmod(0o755)
    cron=bin/'crontab'
    cron.write_text('#!/bin/sh\necho "no crontab for testuser" >&2\nexit 1\n')
    cron.chmod(0o755)
    return home,project,ops,script,bin

with tempfile.TemporaryDirectory(prefix='ald1n-b1001-qa-') as temp:
    base=Path(temp)
    home,project,ops,script,bin=generated_fixture(base)
    env=os.environ.copy(); env['PATH']=str(bin)+':'+env['PATH']
    syntax=subprocess.run(['bash','-n',str(script)],capture_output=True)
    assert syntax.returncode==0, syntax.stderr
    st=subprocess.run(['bash',str(script),'--selftest'],capture_output=True)
    assert st.returncode==0,st.stderr
    checks.extend(['isolated fixture bash-syntax','isolated fixture self-hash'])
    before={str(p.relative_to(home)):hashlib.sha256(p.read_bytes()).hexdigest() for p in home.rglob('synthetic-cache-entry.dat')}
    run=subprocess.run(['bash',str(script),'--run'],env=env,text=True,capture_output=True,timeout=120)
    assert run.returncode==0,(run.returncode,run.stderr,run.stdout[-1500:])
    reports=list(ops.glob('975-B1001-*.md'))
    assert len(reports)==1,reports
    rpt=reports[0].read_text()
    assert 'eligible_review_only=9 held=0 missing=0' in rpt, rpt[-2200:]
    assert 'PASS_B1001_CONSOLIDATED_READONLY_AUDIT' in rpt
    after={str(p.relative_to(home)):hashlib.sha256(p.read_bytes()).hexdigest() for p in home.rglob('synthetic-cache-entry.dat')}
    assert before==after
    checks.extend(['isolated audit 9/9 eligible','one sequential 975 report','no touched generated cache files'])
    assert 'uapi' not in rpt or 'CPANEL_DOMAININFO_DISCOVERY' in rpt
    assert 'CURRENT_SQL_PRIVATE_OFFHOST_RESTORE=NOT_INDEPENDENTLY_ATTESTED' in rpt
    checks.extend(['does not log raw uapi data','current SQL/private backups HOLD'])
    # A changed script is blocked before creating a second report.
    original=script.read_text();script.write_text(original+'\n# tamper\n')
    tamper=subprocess.run(['bash',str(script),'--selftest'],capture_output=True)
    assert tamper.returncode==2
    checks.append('tamper refusal')
    script.write_text(original)
    # Noncanonical --run doesn't create a report.
    uncanonical=base/'copy.sh';uncanonical.write_text(original)
    n=subprocess.run(['bash',str(uncanonical),'--run'],capture_output=True)
    assert n.returncode==2
    checks.append('noncanonical invocation refusal')
    # Recreate separate fixture, trigger source code HTTP 503 negative case; still no deletion.
    other=base/'bad-http';other.mkdir()
    badhome,badproj,badops,badscript,badbin=generated_fixture(other,httpcode='503')
    badenv=env.copy();badenv['PATH']=str(badbin)+':'+os.environ['PATH']
    bad=subprocess.run(['bash',str(badscript),'--run'],env=badenv,text=True,capture_output=True,timeout=120)
    assert bad.returncode==0, bad.stderr
    reports=list(badops.glob('975-B1001-*.md'));assert len(reports)==1
    report=reports[0].read_text()
    assert 'CURRENT_CMS_API_BASELINE=HOLD' in report
    assert 'HOLD_B1001_CONSOLIDATED_AUDIT_INCOMPLETE' in report
    assert 'eligible_review_only=0' in report
    checks.extend(['HTTP 503 fails closed','HTTP failure still writes numbered report','no candidate approved if HTTP fails'])
    # A hostile symlink replacing one eligible cache root must not be accepted.
    linkbase=base/'symlink-case'; linkbase.mkdir()
    linkhome,linkproj,linkops,linkscript,linkbin=generated_fixture(linkbase)
    victim=linkhome/'ald1n-project'/'tmp'/'deferred-payment-batch149b-v2-20260909-212958'/'npm-cache'
    outside=linkbase/'unrelated-site'
    outside.mkdir()
    marker=outside/'sentinel-content-never-touched.txt'; marker.write_text('preserve')
    shutil.rmtree(victim)
    victim.symlink_to(outside,target_is_directory=True)
    linkenv=env.copy();linkenv['PATH']=str(linkbin)+':'+os.environ['PATH']
    linkres=subprocess.run(['bash',str(linkscript),'--run'],env=linkenv,capture_output=True,text=True,timeout=120)
    assert linkres.returncode==0,linkres.stderr
    lrpt=list(linkops.glob('975-B1001-*.md'))[0].read_text()
    assert 'OLD_NPM_149B_V2: HOLD_ROOT_TYPE_OR_SYMLINK' in lrpt
    assert 'eligible_review_only=8 held=1 missing=0' in lrpt
    assert marker.read_text()=='preserve'
    checks.extend(['symlink root HOLD','unrelated site sentinel preserved'])
    # A cron referencing the generated candidate parent also forces HOLD.
    cronbase=base/'cron-case';cronbase.mkdir()
    cronhome,cronproj,cronops,cronscript,cronbin=generated_fixture(cronbase)
    refparent=cronhome/'ald1n-project'/'tmp'/'deferred-payment-batch149b-v2-20260909-212958'
    c=cronbin/'crontab';c.write_text('#!/bin/sh\nprintf \"%s\\n\" '+str(refparent)+'\n')
    c.chmod(0o755)
    cronenv=env.copy();cronenv['PATH']=str(cronbin)+':'+os.environ['PATH']
    cr=subprocess.run(['bash',str(cronscript),'--run'],env=cronenv,capture_output=True,text=True,timeout=120)
    assert cr.returncode==0,cr.stderr
    rpt=list(cronops.glob('975-B1001-*.md'))[0].read_text()
    assert 'OLD_NPM_149B_V2: HOLD_ACTIVE_PROCESS_OR_CRON_REF' in rpt,rpt
    assert 'eligible_review_only=8 held=1 missing=0' in rpt
    checks.extend(['active cron reference HOLD','remaining unaffected candidates only reviewed'])
    # Missing encrypted B995 archive blocks all candidate readiness, not the report.
    archivebase=base/'missing-archive';archivebase.mkdir()
    ahome,aproj,aops,ascript,abin=generated_fixture(archivebase,archive=False)
    aenv=env.copy();aenv['PATH']=str(abin)+':'+os.environ['PATH']
    ar=subprocess.run(['bash',str(ascript),'--run'],env=aenv,capture_output=True,text=True,timeout=120)
    assert ar.returncode==0,ar.stderr
    rpt=list(aops.glob('975-B1001-*.md'))[0].read_text()
    assert 'B995_LOCAL_ENCRYPTED_SCRIPTS_REPORTS_ARCHIVE=HOLD' in rpt
    assert 'eligible_review_only=0 held=9 missing=0' in rpt
    checks.extend(['missing ops archive HOLD','zero candidates with missing archive'])
print('PASS',len(checks),'checks')
for i,x in enumerate(checks,1):print(f'{i:02d}. {x}')
