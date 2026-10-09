const EXPECTED = Object.freeze({
  packageName: 'com.ald1n.mobile', appVersion: '1.0.0',
  runtimeVersion:'1.0.0-build17', owner:'ald1n',
  projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',
  apiUrl:'https://cms.ald1n.com/api/v1',
  channel:'production',profile:'production',track:'production',
  releaseStatus:'completed',appVersionSource:'remote',autoIncrement:true
});

export function validateAuthority(values) {
  if (!values || typeof values !== 'object') return {ok:false,reason:'MISSING_AUTHORITY'};
  const versions=[values.agentsCliVersion,values.easCliVersion,values.helperCliVersion];
  if (versions.some(x=>typeof x!=='string' || !/^\d+\.\d+\.\d+$/.test(x)) || new Set(versions).size!==1) {
    return {ok:false,reason:'CLI_VERSION_CONFLICT'};
  }
  for (const [key,expected] of Object.entries(EXPECTED)) {
    if (values[key] !== expected) return {ok:false,reason:`IDENTITY_MISMATCH:${key}`};
  }
  return {ok:true,reason:'NONE',identity:Object.freeze({...EXPECTED,cliVersion:versions[0]})};
}
