<?php
declare(strict_types=1);

/**
 * Verify a complete, independently pinned snapshot of isolated PHP source.
 * The expected filenames must come from a trusted caller, not from $pins.
 */
final class SourceManifestValidator
{
    /**
     * @param array<string,string> $pins Exact SHA-256 hex digests by relative path.
     * @param list<string> $expectedPaths Independently pinned complete PHP inventory.
     */
    public static function verify(string $root, array $pins, array $expectedPaths): int
    {
        if ($pins === [] || $expectedPaths === [] || !array_is_list($expectedPaths)
            || !str_starts_with($root,'/') || $root === '/' || str_ends_with($root,'/')) {
            throw new \InvalidArgumentException('Missing exact expected source inventory or unsafe isolated root.');
        }

        // realpath() alone would silently resolve a symlink ancestor. Reject
        // every original path component *before* trusting its canonical form.
        $walk='';
        foreach (explode('/', substr($root,1)) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                throw new \InvalidArgumentException('Noncanonical isolated root segment.');
            }
            $walk.='/'.$part;
            if (is_link($walk)) {
                throw new \InvalidArgumentException('Isolated source root has a symlink ancestor.');
            }
        }
        if (!is_dir($root) || realpath($root) !== $root) {
            throw new \InvalidArgumentException('Isolated source root is missing or noncanonical.');
        }
        $candidate=$root.'/candidate';
        if (!is_dir($candidate) || is_link($candidate)) {
            throw new \InvalidArgumentException('Candidate source directory is missing or linked.');
        }
        $required=[];
        foreach ($expectedPaths as $relative) {
            self::assertSafePath($relative);
            if (isset($required[$relative])) {
                throw new \InvalidArgumentException('Duplicate pinned path.');
            }
            $required[$relative]=true;
        }
        $expected=array_keys($required);
        sort($expected,SORT_STRING);
        $submitted=array_keys($pins);
        sort($submitted,SORT_STRING);
        if ($submitted!==$expected) {
            throw new \InvalidArgumentException('Source hash manifest is not the complete expected file set.');
        }

        $discovered=[];
        $it=new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($candidate,\FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $entry) {
            if ($entry->isLink()) {
                throw new \InvalidArgumentException('Candidate source tree contains a symlink.');
            }
            if ($entry->isDir()) continue;
            if (!$entry->isFile()) {
                throw new \InvalidArgumentException('Candidate source contains an unsupported filesystem entry.');
            }
            if (str_ends_with($entry->getFilename(),'.php')) {
                $relative='candidate/'.substr($entry->getPathname(),strlen($candidate)+1);
                self::assertSafePath($relative);
                $discovered[]=$relative;
            }
        }
        sort($discovered,SORT_STRING);
        if ($discovered!==$expected) {
            throw new \InvalidArgumentException('Actual candidate PHP inventory differs from the pinned complete inventory.');
        }
        foreach ($expected as $relative) {
            $hash=$pins[$relative];
            if (!is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/D',$hash)!==1) {
                throw new \InvalidArgumentException('Invalid pinned source digest.');
            }
            $full=$root.'/'.$relative;
            if (is_link($full) || !is_file($full)) {
                throw new \InvalidArgumentException('Pinned source file is missing or linked.');
            }
            $actual=hash_file('sha256',$full);
            if (!is_string($actual) || !hash_equals($hash,$actual)) {
                throw new \InvalidArgumentException('Pinned isolated source file changed.');
            }
        }
        return count($expected);
    }

    private static function assertSafePath(mixed $relative): void
    {
        if (!is_string($relative) || !str_starts_with($relative,'candidate/')
            || preg_match('~\Acandidate(?:/[a-zA-Z0-9_-][a-zA-Z0-9_.-]*)+\.php\z~D',$relative)!==1) {
            throw new \InvalidArgumentException('Noncanonical candidate source path.');
        }
        foreach (explode('/',$relative) as $component) {
            if ($component === '.' || $component === '..' || str_starts_with($component,'.')) {
                throw new \InvalidArgumentException('Hidden or parent traversal source component.');
            }
        }
    }
}
