<?php

class Zeclib_Phing_TaskMigrationLogger implements Zeclib_MigrationLogger
{
    /**
     * @var Task
     */
    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function log($message, $type = Zeclib_MigrationLogger::TYPE_INFO) {
        static $levels = array(
            Zeclib_MigrationLogger::TYPE_DEBUG => Project::MSG_DEBUG,
            Zeclib_MigrationLogger::TYPE_INFO => Project::MSG_INFO,
            Zeclib_MigrationLogger::TYPE_WARNING => Project::MSG_WARN,
            Zeclib_MigrationLogger::TYPE_ERROR => Project::MSG_ERR,
        );

        $this->task->log($message, $levels[$type]);
    }
}
