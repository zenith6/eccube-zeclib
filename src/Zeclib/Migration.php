<?php

/**
 * マイグレーションを表します。
 *
 * @author Seiji Nitta
 */
abstract class Zeclib_Migration
{
    /**
     * マイグレーションバージョン
     *
     * 各マイグレーションを適用する順序を解決するために使用されます。
     * 詳細は Zeclib_Migrator::sort() を参照して下さい。
     *
     * @var string
     * @see Zeclib_Migrator::sort()
     */
    public $version;

    /**
     * @var bool
     */
    public $applied = false;

    /**
     * @var SC_Query_Ex
     */
    public $query;

    /**
     * @var Zeclib_MigrationLogger
     */
    public $logger;

    /**
     * @param string $version マイグレーションバージョン
     * @param SC_Query_Ex $query
     */
    public function __construct($version, SC_Query_Ex $query)
    {
        $this->version = $version;
        $this->query = $query;
    }

    /**
     * マイグレーションを適用します。
     * 適用を中断する場合は例外を発生させて下さい。
     */
    abstract public function up();

    /**
     * マイグレーションを取り消します。
     * 取り消しを中断する場合は例外を発生させて下さい。
     */
    abstract function down();

    /**
     * ログを記録します。
     * マイグレーション中に残したいメッセージを指定して下さい。
     *
     * @param string $message
     * @param int $type
     */
    public function log($message, $type = Zeclib_MigrationLogger::TYPE_INFO)
    {
        $this->logger->log($message, $type);
    }
}
