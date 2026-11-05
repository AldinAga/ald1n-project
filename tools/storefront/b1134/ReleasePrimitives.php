<?php
declare(strict_types=1);

/** INCOMPLETE DRAFT LIBRARY. No hosting entrypoint or production authorization. */
final class ReleasePrimitives
{
    public static function offConfig(string $original,string $expectedSHA256):string
    {
        if(!hash_equals($expectedSHA256,hash('sha256',$original)))throw new RuntimeException('CONFIG_SOURCE_HASH');
        $prefix="<?php\ndeclare(strict_types=1);\n";
        if(!str_starts_with($original,$prefix))throw new RuntimeException('CONFIG_PREFIX');
        $body=substr($original,strlen($prefix));
        return $prefix."// Owned runtime-only OFF clamp; original SHA256: ".$expectedSHA256."\n".
            '$values=(static function():array {'."\n".$body."\n})();\n".
            '$values[\'enabled\']=false;'."\n".'$values[\'checkout_enabled\']=false;'."\nreturn \$values;\n";
    }

    public static function switchDirectories(string $active,string $candidate,string $rollback):array
    {
        foreach([$active,$candidate] as $path)if(is_link($path)||!is_dir($path)||realpath($path)!==$path)throw new RuntimeException('DIRECTORY_CONFINEMENT');
        if(file_exists($rollback)||is_link($rollback)||dirname($rollback)!==dirname($active))throw new RuntimeException('ROLLBACK_COLLISION');
        if(stat($active)['dev']!==stat($candidate)['dev'])throw new RuntimeException('CROSS_FILESYSTEM');
        if(!rename($active,$rollback))throw new RuntimeException('OLD_RELEASE_RENAME');
        if(!rename($candidate,$active)){
            if(!rename($rollback,$active))throw new RuntimeException('RESTORE_RENAME_FAILED_HOLD_MAINTENANCE');
            throw new RuntimeException('CANDIDATE_RENAME_FAILED_OLD_RESTORED');
        }
        return ['active'=>$active,'rollback'=>$rollback,'candidateMovedFrom'=>$candidate,'oldReleasePreserved'=>true];
    }

    public static function rollbackDirectories(array $receipt,string $failedRelease):void
    {
        $active=$receipt['active'];$rollback=$receipt['rollback'];
        if(is_link($active)||is_link($rollback)||!is_dir($active)||!is_dir($rollback)||file_exists($failedRelease)||is_link($failedRelease)||dirname($failedRelease)!==dirname($active))throw new RuntimeException('ROLLBACK_CONFINEMENT');
        if(!rename($active,$failedRelease))throw new RuntimeException('FAILED_RELEASE_RETAIN');
        if(!rename($rollback,$active)){
            if(!rename($failedRelease,$active))throw new RuntimeException('ROLLBACK_AND_RESTORE_FAILED_HOLD_MAINTENANCE');
            throw new RuntimeException('ROLLBACK_FAILED_CANDIDATE_RESTORED');
        }
    }

    public static function assertCompleteHostGates(array $facts):void
    {
        foreach(['candidateCompleteManifest','privateFilesPreserved','ownedMaintenance','workersQuiescent','authenticatedIncomingWebMapping','webHandlerAttested','webOpcacheInvalidation','cleanupProven','candidateOffOldSchemaHTTP'] as $gate){
            if(($facts[$gate]??false)!==true)throw new RuntimeException('UNFINISHED_INSTALLER_GATE_'.$gate);
        }
    }
}
