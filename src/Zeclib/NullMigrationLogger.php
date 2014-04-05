<?php

class Zeclib_NullMigrationLogger implements Zeclib_MigrationLogger
{
    public function log($message, $type = Zeclib_MigrationLogger::TYPE_INFO)
    {
    }
}
