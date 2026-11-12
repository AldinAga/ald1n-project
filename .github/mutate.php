<?php
// Throwaway helper: replace exactly one occurrence or fail.
[, $file, $from, $to] = $argv;
$s = file_get_contents($file);
if (substr_count($s, $from) !== 1) {
    fwrite(STDERR, "occurrences=".substr_count($s, $from)." for: $from\n");
    exit(2);
}
file_put_contents($file, str_replace($from, $to, $s));
