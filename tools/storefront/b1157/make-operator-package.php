<?php
declare(strict_types=1);
/**
 * Builds the owner's hand-over folder from the CI artifact of the B1157 installer run: b1157-operator.zip, b1157-operator.zip.sha256 and OPERATOR.md (from OPERATOR.tpl.md, with the real size and SHA-256).
 *   php tools/storefront/b1157/make-operator-package.php [--run <run id>] [--out <dir>] [--pr <number>]
 * Without --run it takes the newest SUCCESSFUL run of workflow b1157-installer.yml on the current branch. Refuses when the run did not succeed, when the run's commit carries another Payload.php than this working copy
 * (the artifact would not be this payload), or when the zip does not match its .sha256. Needs `gh` (logged in) and `git`. Nothing is uploaded anywhere.
 * @author Ald1n <https://ald1n.com>
 */
$args=array_slice($argv,1);$run=null;$out=null;$pr='?';
for($i=0;$i<count($args);$i++){if($args[$i]==='--run')$run=$args[++$i];elseif($args[$i]==='--out')$out=$args[++$i];elseif($args[$i]==='--pr')$pr=$args[++$i];else throw new RuntimeException('MAKE_ARGUMENT_'.$args[$i]);}
$here=__DIR__;$repo=dirname($here,3);
$sh=static function(array $a,?string $cwd=null):string{$p=proc_open($a,[1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);$o=stream_get_contents($pipes[1]);$e=stream_get_contents($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException('MAKE_COMMAND_'.$a[0].'_'.$a[1].': '.trim($e));return $o;};
$branch=trim($sh(['git','-C',$repo,'rev-parse','--abbrev-ref','HEAD']));
if($run===null){$list=json_decode($sh(['gh','run','list','--workflow','b1157-installer.yml','--branch',$branch,'--status','success','--limit','1','--json','databaseId'],$repo),true,8,JSON_THROW_ON_ERROR);if(!isset($list[0]['databaseId']))throw new RuntimeException('MAKE_NO_SUCCESSFUL_RUN_ON_'.$branch);$run=(string)$list[0]['databaseId'];}
$info=json_decode($sh(['gh','run','view',$run,'--json','conclusion,headSha,headBranch,workflowName'],$repo),true,8,JSON_THROW_ON_ERROR);
if(($info['conclusion']??null)!=='success')throw new RuntimeException('MAKE_RUN_NOT_SUCCESSFUL');
$remote=$sh(['git','-C',$repo,'show',$info['headSha'].':tools/storefront/b1157/Payload.php']);
if(str_replace("\r","",$remote)!==str_replace("\r","",(string)file_get_contents($here.'/Payload.php')))throw new RuntimeException('MAKE_PAYLOAD_OF_THE_RUN_IS_NOT_THIS_WORKING_COPY');
require $here.'/Payload.php';
$out??=dirname($repo).'/b1157-pkg';if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('MAKE_OUT');
$tmp=$out.'/dl-'.$run;if(!is_dir($tmp)&&!mkdir($tmp,0755,true))throw new RuntimeException('MAKE_TMP');
$sh(['gh','run','download',$run,'--name','b1157-operator-package','--dir',$tmp],$repo);
foreach(['b1157-operator.zip','b1157-operator.zip.sha256','b1157-digests.tsv'] as $f)if(!is_file($tmp.'/'.$f))throw new RuntimeException('MAKE_ARTIFACT_LACKS_'.$f);
$zipSha=hash_file('sha256',$tmp.'/b1157-operator.zip');$shaFile=(string)file_get_contents($tmp.'/b1157-operator.zip.sha256');
if(!preg_match('/^([a-f0-9]{64})  b1157-operator\.zip\n?$/D',$shaFile,$m)||$m[1]!==$zipSha)throw new RuntimeException('MAKE_ZIP_DOES_NOT_MATCH_ITS_SHA256');
foreach(['b1157-operator.zip','b1157-operator.zip.sha256','b1157-digests.tsv'] as $f)copy($tmp.'/'.$f,$out.'/'.$f);
$za=new ZipArchive();$za->open($out.'/b1157-operator.zip');$provJson=json_decode((string)$za->getFromName('incoming/b1157-provenance.json'),true);$za->close();
$hz=$provJson['orderHazards']??[];$tight=B1157Payload::TIGHT_ORDER;
$hazards=($hz===[]&&$tight===[])?'nema.':implode(' ',array_map(static fn($x)=>$x.'.',$hz)).($tight===[]?'':' Servisi koji uklanjaju i dodaju javne metode su ubačeni u grupu C između starih i novih pozivalaca: '.json_encode($tight,JSON_UNESCAPED_SLASHES).'.');
$tpl=(string)file_get_contents($here.'/OPERATOR.tpl.md');
$map=['{{SRC8}}'=>substr(B1157Payload::SOURCE_COMMIT,0,8),'{{FILES}}'=>(string)B1157Payload::FILES,'{{NEW}}'=>(string)B1157Payload::NEW_FILES,'{{MOD}}'=>(string)B1157Payload::MODIFIED_FILES,'{{MIGRATIONS}}'=>implode(', ',array_map(static fn($x)=>'`'.$x.'`',B1157Payload::NEW_MIGRATIONS)),'{{TREE}}'=>(string)B1157Payload::TREE_FILES_AFTER,'{{ROUTES_AFTER}}'=>(string)B1157Payload::ROUTES_AFTER,
    '{{BRANCH}}'=>$info['headBranch'],'{{PR}}'=>$pr,'{{RUN}}'=>$run,'{{ZIP_BYTES}}'=>(string)filesize($out.'/b1157-operator.zip'),'{{ZIP_SHA}}'=>$zipSha,'{{SHA_FILE_BYTES}}'=>(string)strlen($shaFile),'{{HAZARDS}}'=>$hazards];
file_put_contents($out.'/OPERATOR.md',strtr($tpl,$map));
foreach(glob($tmp.'/*')?:[] as $f)unlink($f);rmdir($tmp);
echo json_encode(['out'=>$out,'run'=>$run,'zipSHA256'=>$zipSha,'zipBytes'=>filesize($out.'/b1157-operator.zip'),'source'=>B1157Payload::SOURCE_COMMIT],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
