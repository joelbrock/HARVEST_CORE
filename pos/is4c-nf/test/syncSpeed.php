<?php
include(dirname(__FILE__) . '/test_env.php');
function nl() { return php_sapi_name() === 'cli' ? "\n" : '<br />'; }

echo 'Beginning upload' . nl();

$time = microtime(true);
Database::uploadToServer();
$done = microtime(true);

echo 'Upload complete' . nl();

printf('Elapsed: %.4f%s', ($done-$time), nl());
