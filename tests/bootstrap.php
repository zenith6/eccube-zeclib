<?php

define('ZECLIB_TEST_BASE', dirname(__FILE__));
define('ZECLIB_TEST_FILES_DIR', ZECLIB_TEST_BASE . '/_files');
define('ZECLIB_TEST_VENDOR_DIR', dirname(__FILE__) . '/../vendor');

$loader = require ZECLIB_TEST_VENDOR_DIR . '/autoload.php';
$loader->add('Zeclib_', ZECLIB_TEST_BASE);
unset($loader);
