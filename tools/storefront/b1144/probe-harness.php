<?php
declare(strict_types=1);
// CI-only CLI harness: replays one HTTP request's server variables into the real probe file.
// argv[1]=probe path, argv[2]=JSON server variables, argv[3..]=scripts to compile into OPcache first.
$server=json_decode($argv[2],true,8,JSON_THROW_ON_ERROR);foreach($server as $key=>$value)$_SERVER[$key]=$value;
// A "vanish:" prefix compiles the script and then deletes its file (a script only the other tree has).
foreach(array_slice($argv,3) as $script){$vanish=str_starts_with($script,'vanish:');$script=$vanish?substr($script,7):$script;if(function_exists('opcache_compile_file'))@opcache_compile_file($script);if($vanish)unlink($script);}
require $argv[1];
