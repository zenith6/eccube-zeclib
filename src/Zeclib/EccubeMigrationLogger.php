<?php

class Zeclib_Phing_EccubeMigrationLogger implements Zeclib_MigrationLogger
{
    public $defaultLogFile = null;

    public function log($message, $type = Zeclib_MigrationLogger::TYPE_INFO) {
        switch ($type) {
            case Zeclib_MigrationLogger::TYPE_DEBUG:
                GC_Utils_Ex::gfPrintLog($message, DEBUG_LOG_REALFILE);
                break;

            case Zeclib_MigrationLogger::TYPE_WARNING:
            case Zeclib_MigrationLogger::TYPE_ERROR:
                GC_Utils_Ex::gfPrintLog($message, ERROR_LOG_REALFILE);
                break;

            default:
                GC_Utils_Ex::gfPrintLog($message, $defaultLogFile);
                break;
        }
    }
}
