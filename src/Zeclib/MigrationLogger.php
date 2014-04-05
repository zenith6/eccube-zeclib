<?php

interface Zeclib_MigrationLogger
{
    const TYPE_DEBUG = 0;

    const TYPE_INFO = 1;

    const TYPE_WARNING = 2;

    const TYPE_ERROR = 3;

    public function log($message, $type = Zeclib_MigrationLogger::TYPE_INFO);
}
