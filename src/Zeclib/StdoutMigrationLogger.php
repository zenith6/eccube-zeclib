<?php

class Zeclib_StdoutMigrationLogger implements Zeclib_MigrationLogger
{
    public function log($message, $type = Zeclib_MigrationLogger::TYPE_INFO)
    {
        switch ($type) {
            case Zeclib_MigrationLogger::LOG_ERROR:
                $out = fopen('php://stderr', 'w');
                break;

            default:
                $out = fopen('php://stdout', 'w');
                break;
        }
        fwrite($out, $message);
    }
}
