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

    /**
     * SC_Query_Ex で使用している MDB2 を取得します。
     *
     * @param $options array
     * @return MDB2_Driver_Manager_Common
     */
    protected function getMDB2()
    {
        $mdb2 = $this->query->conn;
        $mdb2->loadModule('Manager');
        $mdb2->loadModule('Reverse');

        return $mdb2;
    }

    /**
     * テーブルを作成します。
     *
     * @param string $tableName
     * @param array $def
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::createTable()
     */
    protected function createTable($tableName, array $def)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->createTable($tableName, $def['fields']);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        if (isset($def['indexes'])) {
            foreach ($def['indexes'] as $name => $index) {
                $this->createIndex($tableName, $name, $index);
            }
        }

        if (isset($def['constraints'])) {
            foreach ($def['constraints'] as $name => $constraint) {
                $this->createConstraint($tableName, $name, $constraint);
            }
        }

        $message = sprintf('Table "%s" was created.', $tableName);
        $this->log($message);
    }

    /**
     * テーブルを削除します。
     *
     * @param string $tableName
     * @param array $def
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::dropTable()
     */
    protected function dropTable($tableName, array $def)
    {
        if (isset($def['constraints'])) {
            foreach ($def['constraints'] as $name => $constraint) {
                $this->dropConstraint($tableName, $name, $constraint);
            }
        }

        if (isset($def['indexes'])) {
            foreach ($def['indexes'] as $name => $index) {
                $this->dropIndex($tableName, $name, $index);
            }
        }

        $mdb2 = $this->getMDB2();
        $result = $mdb2->dropTable($tableName);
        if (PEAR::isError($result)) {
            throw new Zeclib_MigrationException($result->getMessage() . ' ' . $result->getDebugInfo(), $result
                ->getCode());
        }

        $message = sprintf('Table "%s" was dropped.', $tableName);
        $this->log($message);
    }

    /**
     * インデックスを作成します。
     *
     * @param string $tableName
     * @param string $name
     * @param array $def
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::createIndex()
     */
    protected function createIndex($tableName, $name, array $def)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->createIndex($tableName, $name, $def);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Index "%s" was created to "%s".', $name, $tableName);
        $this->log($message);
    }

    /**
     * インデックスを削除します。
     *
     * @param string $tableName
     * @param string $name
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::dropIndex()
     */
    protected function dropIndex($tableName, $name)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->dropIndex($tableName, $name);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Index "%s" was created to "%s".', $name, $tableName);
        $this->log($message);
    }

    /**
     * 制約を作成します。
     *
     * @param string $tableName
     * @param string $name
     * @param array $def
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::createConstraint()
     */
    protected function createConstraint($tableName, $name, array $def)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->createConstraint($tableName, $name, $def);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Constraint "%s" was created to "%s".', $name, $tableName);
        $this->log($message);
    }

    /**
     * 制約を削除します。
     *
     * @param string $tableName
     * @param string $name
     * @param bool $primaryKey
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::dropConstraint()
     */
    protected function dropConstraint($tableName, $name, $primaryKey)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->dropConstraint($tableName, $name, $primaryKey);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Constraint "%s" was dropped from "%s".', $name, $tableName);
        $this->log($message);
    }

    /**
     * シーケンスを作成します。
     *
     * @param string $name
     * @param string $start
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::createSequence()
     */
    protected function createSequence($name, $start)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->createSequence($name, $start);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Sequence "%s" was created.', $name);
        $this->log($message);
    }

    /**
     * シーケンスを削除します。
     *
     * @param string $name
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::dropSequence()
     */
    protected function dropSequence($name)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->dropSequence($name);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Sequence "%s" was dropped.', $name);
        $this->log($message);
    }

    /**
     * テーブルを変更します。
     *
     * @param string $tableName
     * @param array $change
     * @throws Zeclib_MigrationException
     * @see MDB2_Driver_Manager_Common::alterTable()
     */
    protected function alterTable($tableName, array $change)
    {
        $mdb2 = $this->getMDB2();
        $result = $mdb2->alterTable($tableName, $change);
        if (PEAR::isError($result)) {
            $message = $result->getMessage() . ' ' . $result->getDebugInfo();
            throw new Zeclib_MigrationException($message, $result->getCode());
        }

        $message = sprintf('Table "%s" was altered.', $tableName);
        $this->log($message);
    }
}
