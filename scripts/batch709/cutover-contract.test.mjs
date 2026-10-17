import test from 'node:test';
import assert from 'node:assert/strict';
import {assessReleaseReadiness, CUTOVER_LAYOUT, inspectBackupPair} from './cutover-contract.mjs';
const SHA='a'.repeat(40), HASH='d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef';
function base(){return {
  sourceSha:SHA,expectedSourceSha:SHA,
  documentRoot:'/home/icaffeco/ald1n-project/apps/cms/current/public',
  expectedDocumentRoot:'/home/icaffeco/ald1n-project/apps/cms/current/public',
  apacheRuntimeAttested:true,sourceCI:true,independentReview:true,ownerReleaseApproval:true,
  hostOverrideSha:HASH,expectedOverrideSha:HASH,hostProtectedSnapshots:8,
  stagingSourceIndependent:true,stagingSameDevice:true,stagingTests:true,
  backOutRollbackDocumented:true,maintenanceWindowConfirmed:true,lockWindowApproved:true,
  actualDbVersion:'10.11.19-MariaDB-cll-lve',expectedDbVersion:'10.11.19-MariaDB-cll-lve',
  backupPair:[{id:208,ageHours:3,verified:true},{id:207,ageHours:19,verified:true}],
  isolatedRestoreProven:true,privilegedMdlReview:true,currentLayout:CUTOVER_LAYOUT.SYMLINK,
};}
test('all validated facts allow planning only, never deployment',()=>{const r=assessReleaseReadiness(base());assert.equal(r.readyForManualDeploymentPlan,true);assert.equal(r.deployed,false);assert.equal(r.productionMutations,0);});
test('rejects stale source',()=>{const x=base();x.sourceSha='b'.repeat(40);assert.equal(assessReleaseReadiness(x).readyForManualDeploymentPlan,false);});
test('rejects unproven external reviewer',()=>{const x=base();x.independentReview=false;assert.match(assessReleaseReadiness(x).blockers.join(' '),/independent_review/);});
test('rejects missing explicit owner authorization',()=>{const x=base();x.ownerReleaseApproval=false;assert.match(assessReleaseReadiness(x).blockers.join(' '),/owner_release_authorization/);});
test('cPanel UI path alone not runtime parity',()=>{const x=base();x.apacheRuntimeAttested=false;assert.match(assessReleaseReadiness(x).blockers.join(' '),/apache_runtime/);});
test('rejects different DocumentRoot',()=>{const x=base();x.documentRoot='/home/icaffeco/public_html';assert.equal(assessReleaseReadiness(x).readyForManualDeploymentPlan,false);});
test('refuses changes to protected .htaccess hash',()=>{const x=base();x.hostOverrideSha='f'.repeat(64);assert.match(assessReleaseReadiness(x).blockers.join(' '),/htaccess/);});
test('no bypass of eight protected snapshots',()=>{const x=base();x.hostProtectedSnapshots=7;assert.equal(assessReleaseReadiness(x).readyForManualDeploymentPlan,false);});
test('physical directory cannot claim atomic symlink swap',()=>{const x=base();x.currentLayout=CUTOVER_LAYOUT.DIRECTORY;assert.match(assessReleaseReadiness(x).blockers.join(' '),/physical_directory/);});
test('missing verified staging and same disk rejects',()=>{const x=base();x.stagingSourceIndependent=false;x.stagingSameDevice=false;assert.equal(assessReleaseReadiness(x).readyForManualDeploymentPlan,false);});
test('backup pair requires exactly two unique logically verified records',()=>{assert.equal(inspectBackupPair([{id:1,ageHours:2,verified:true},{id:1,ageHours:3,verified:true}]).ok,false);assert.equal(inspectBackupPair([{id:1,ageHours:2,verified:true}]).ok,false);});
test('latest verified backup must be fresher than 24h',()=>{const x=base();x.backupPair[0].ageHours=26;assert.match(assessReleaseReadiness(x).blockers.join(' '),/backup/);});
test('restore deferred means release remains HOLD',()=>{const x=base();x.isolatedRestoreProven=false;assert.match(assessReleaseReadiness(x).blockers.join(' '),/restore/);});
test('performance_schema privilege gap remains HOLD',()=>{const x=base();x.privilegedMdlReview=false;assert.match(assessReleaseReadiness(x).blockers.join(' '),/mdl/);});
test('same family different patch or vendor is not automatically approved',()=>{const x=base();x.actualDbVersion='10.11.20-MariaDB';assert.match(assessReleaseReadiness(x).blockers.join(' '),/db_version/);});
test('QA passing without maintenance window still HOLD',()=>{const x=base();x.maintenanceWindowConfirmed=false;assert.match(assessReleaseReadiness(x).blockers.join(' '),/maintenance_window/);});
test('output carries no executable deploy action',()=>{const r=assessReleaseReadiness(base());assert.equal('commands' in r,false);assert.equal('deploy' in r,false);});
