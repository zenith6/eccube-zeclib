<?php

/**
 * マイグレーションの適用を管理します。
 *
 * @author Seiji Nitta
 */
class Zeclib_Migrator
{
    /**
     * @var Zeclib_MigrationStorage
     */
    public $storage;

    /**
     * @var SC_Query_Ex
     */
    public $query;

    /**
     * @var Zeclib_Migration_Logger
     */
    public $logger;

    public function __construct(Zeclib_MigrationStorage $storage, SC_Query_Ex $query)
    {
        $this->storage = $storage;
        $this->query = $query;

        $this->logger = self::getDefaultLogger();
    }

    protected static $defaultLogger;

    public static function getDefaultLogger()
    {
        if (!self::$defaultLogger) {
            self::$defaultLogger = new Zeclib_NullMigrationLogger();
        }

        return self::$defaultLogger;
    }

    public function apply(array $migrations)
    {
        $migrations = $this->sort($migrations, SORT_ASC);
        $applieds = 0;
        try {
            foreach ($migrations as $migration) {
                $this->query->begin();

                if ($this->storage->isAppliedVersion($migration->version)) {
                    $this->logger->log(sprintf('Apply migration skipped, Already applied.: {system:"%s", version:"%s"}', $this->storage->system, $migration->version));
                    continue;
                }

                $migration->up();
                $migration->applied = true;
                $this->storage->markApplied($migration->version);

                $this->query->commit();
                $this->logger->log(sprintf('Apply migration successful: {system:"%s", version:"%s"}', $this->storage->system, $migration->version));

                $applieds++;
            }
        } catch (Exception $e) {
            if ($this->query->inTransaction()) {
                $this->query->rollback();
            }
                $this->logger->log(sprintf('Apply migration failed: {system:"%s", version:"%s"}', $this->storage->system, $migration->version));

            throw $e;
        }

        return $applieds;
    }

    public function revert(array $migrations)
    {
        $migrations = $this->sort($migrations, SORT_DESC);
        $reverteds = 0;
        try {
            foreach ($migrations as $migration) {
                $this->query->begin();

                if (!$this->storage->isAppliedVersion($migration->version)) {
                    $this->logger->log(sprintf('Revert migration skipped, Already reverted.: {system:"%s", version:"%s"}', $this->storage->system, $migration->version));
                    continue;
                }

                $migration->down();
                $migration->applied = false;
                $this->storage->markReverted($migration->version);

                $this->query->commit();
                $this->logger->log(sprintf('Revert migration successful: {system:"%s", version:"%s"}', $this->storage->system, $migration->version));

                $reverteds++;
            }

            $this->query->commit();
        } catch (Exception $e) {
            if ($this->query->inTransaction()) {
                $this->query->rollback();
            }
                $this->logger->log(sprintf('Revert migration failed: {system:"%s", version:"%s"}', $this->storage->system, $migration->version));

            throw $e;
        }

        return $reverteds;
    }

    public function clear()
    {
        $this->storage->clear();
    }

    public function sort($migrations, $order = SORT_ASC)
    {
        $keys = array();
        foreach ($migrations as $mig) {
            $keys[] = $mig->version;
        }
        array_multisort($keys, SORT_STRING, $order, $migrations);

        return $migrations;
    }

    public function getAllVersions()
    {
        return $this->storage->getAllVersions();
    }

    public function getAppliedVersions()
    {
        return $this->storage->getAppliedVersions();
    }

    public function getPendingVersions()
    {
        return $this->storage->getPendingVersions();
    }

    protected function filterVersion($versions, $from = null, $to = null)
    {
        $filtered = array();
        foreach ($versions as $version) {
            $include = $from === null || strcmp($version, $from) >= 0;
            $include = $include && ($to === null || strcmp($version, $to) <= 0);
            if ($include) {
                $filtered[] = $version;
            }
        }

        return $filtered;
    }

    public function up($from = null, $to = null, $ignoreMissing = false)
    {
        $versions = $this->getPendingVersions();
        $versions = $this->filterVersion($versions, $from, $to);
        sort($versions, SORT_STRING);

        $uppeds = 0;
        foreach ($versions as $version) {
            try {
                $migration = $this->loadMigration($version);
            } catch (Zeclib_MigrationException $e) {
                if ($ignoreMissing) {
                    continue;
                }

                throw $e;
            }

            $this->apply(array($migration));
            $uppeds++;
        }

        return $uppeds;
    }

    public function down($from = null, $to = null, $ignoreMissing = false)
    {
        $versions = $this->getAppliedVersions();
        $versions = $this->filterVersion($versions, $from, $to);
        rsort($versions, SORT_STRING);

        $downeds = 0;
        foreach ($versions as $version) {
            try {
                $migration = $this->loadMigration($version);
            } catch (Zeclib_MigrationException $e) {
                if ($ignoreMissing) {
                    continue;
                }

                throw $e;
            }

            $this->revert(array($migration));
            $downeds++;
        }

        return $downeds;
    }

    public function loadMigration($version)
    {
        $migration = $this->storage->loadMigration($version);
        $migration->logger = $this->logger;

        return $migration;
    }
}
