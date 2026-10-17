// Fail-closed pure release-readiness contract. NO deployment or production IO.
// Implement actual host changes only in separately authorized Batch711.
const SHA40=/^[a-f0-9]{40}$/;
const SHA64=/^[a-f0-9]{64}$/;
export const CUTOVER_LAYOUT=Object.freeze({DIRECTORY:'directory',SYMLINK:'symlink'});

export function inspectBackupPair(rows){
  if(!Array.isArray(rows)||rows.length!==2)return {ok:false,reason:'two_backups_required'};
  const [latest,previous]=rows;
  if(!rows.every(r=>Number.isInteger(r?.id)&&r.id>0&&r.verified===true&&
       Number.isFinite(r.ageHours)&&r.ageHours>=0)||latest.id===previous.id){
    return {ok:false,reason:'backup_integrity_or_identity'};
  }
  if(latest.ageHours>=24||previous.ageHours<latest.ageHours){
    return {ok:false,reason:'backup_freshness_or_order'};
  }
  return {ok:true,latestId:latest.id,previousId:previous.id};
}

export function assessReleaseReadiness(a={}){
  const blockers=[];
  if(!SHA40.test(a.sourceSha)||!SHA40.test(a.expectedSourceSha)||a.sourceSha!==a.expectedSourceSha)blockers.push('source_sha_mismatch');
  if(a.sourceCI!==true)blockers.push('exact_source_qa_not_verified');
  const wanted='/home/icaffeco/ald1n-project/apps/cms/current/public';
  if(a.expectedDocumentRoot!==wanted||a.documentRoot!==wanted)blockers.push('wrong_document_root');
  if(a.apacheRuntimeAttested!==true)blockers.push('apache_runtime_vhost_not_attested');
  if(a.independentReview!==true)blockers.push('independent_review_required');
  if(a.ownerReleaseApproval!==true)blockers.push('owner_release_authorization_required');
  if(!SHA64.test(a.expectedOverrideSha)||a.hostOverrideSha!==a.expectedOverrideSha)blockers.push('htaccess_override_sha_mismatch');
  if(a.hostProtectedSnapshots!==8)blockers.push('protected_host_snapshots_not_attested');
  if(a.stagingSourceIndependent!==true||a.stagingSameDevice!==true||a.stagingTests!==true)blockers.push('verified_isolated_staging_missing');
  if(a.backOutRollbackDocumented!==true)blockers.push('rollback_runbook_not_attested');
  if(a.maintenanceWindowConfirmed!==true)blockers.push('maintenance_window_not_approved');
  if(a.lockWindowApproved!==true||a.privilegedMdlReview!==true)blockers.push('privileged_mdl_review_missing');
  if(a.isolatedRestoreProven!==true)blockers.push('isolated_restore_drill_not_approved_or_completed');
  if(a.actualDbVersion!==a.expectedDbVersion||a.expectedDbVersion!=='10.11.19-MariaDB-cll-lve')blockers.push('db_version_parity_not_attested');
  if(!inspectBackupPair(a.backupPair).ok)blockers.push('two_verified_backups_and_fresh_latest_required');
  if(a.currentLayout!==CUTOVER_LAYOUT.SYMLINK){
    blockers.push(a.currentLayout===CUTOVER_LAYOUT.DIRECTORY?
      'physical_directory_cannot_atomic_symlink_swap':'unknown_current_path_layout');
  }
  return {
    contract:'Ald1n Batch709 gated CloudLinux cutover / plan only',
    readyForManualDeploymentPlan:blockers.length===0,
    blockers,
    deployed:false,
    productionMutations:0,
    next:'Independent signoff and controlled Batch711 installer required before ANY production action',
  };
}
