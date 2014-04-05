<?php

class Zeclib_DefaultMigrationStorage extends Zeclib_MigrationStorage
{
    /**
     * @var array
     */
    public $containerDirectories = array();

    /**
     * @var SC_Query_Ex
     */
    public $query;

    /**
     * @var string
     */
    public $system = 'default';

    /**
     * @var string
     */
    public $migrationBaseType = 'Zeclib_Migration';

    /**
     * @var string
     */
    public $versionTable = 'dtb_migration';

    /**
     * @var string
     */
    public $systemColumn = 'system';

    /**
     * @var string
     */
    public $versionColumn = 'version';

    /**
     * @var array
     */
    protected static $migrationClasses = array();

    public function __construct(SC_Query_Ex $query, $system)
    {
        $this->query = $query;
        $this->system = $system;
    }

    private $containerCache;

    /**
     * @param string $refresh
     * @throws Zeclib_MigrationException
     * @return array<string>
     */
    private function findContainers($refresh = false)
    {
        if (!$refresh && $this->containerCache !== null) {
            return $this->containerCache;
        }

        $containers = array();
        foreach ($this->containerDirectories as $dir) {
            $files = new DirectoryIterator($dir);
            foreach ($files as $file) {
                if (!$file->isFile() || $file->getExtension() != 'php') {
                    continue;
                }

                $basename = $file->getBasename('.php');
                list($version) = (array)explode('_', $basename, 2);
                if (array_key_exists($version, $containers)) {
                    $message = sprintf('Duplicated version %s found at "%s" and "%s"', $version, $file->getRealPath(), $containers[$version]);
                    throw new Zeclib_MigrationException($message);
                }
                $containers[$version] = $file->getPathname();
            }
        }
        $this->containerCache = $containers;

        return $containers;
    }

    public function getAllVersions()
    {
        $containers = $this->findContainers();
        $versions = array_keys($containers);

        return $versions;
    }

    public function getAppliedVersions()
    {
        $where = "{$this->systemColumn} = ?";
        $whereValues = array($this->system);
        $rows = $this->query->select($this->versionColumn, $this->versionTable, $where, $whereValues);
        $versions = array();
        foreach ($rows as $row) {
            $versions[] = $row[$this->versionColumn];
        }

        return $versions;
    }

    public function getPendingVersions()
    {
        $all = $this->getAllVersions();
        $applieds = $this->getAppliedVersions();
        $pendings = array_diff($all, $applieds);

        return $pendings;
    }

    /**
     * @param string $version マイグレーションバージョン
     * @return Zeclib_Migration
     */
    public function loadMigration($version)
    {
        $containers = $this->findContainers();
        if (!array_key_exists($version, $containers)) {
            $message = sprintf('Not found migration container. "%s"', $version);
            throw new Zeclib_MigrationException($message);
        }

        $migraClass = null;
        if (array_key_exists($version, self::$migrationClasses)) {
            $className = self::$migrationClasses[$version];
            $migraClass = new ReflectionClass($className);
        } else {
            $container = $containers[$version];
            $before = get_declared_classes();
            require_once $container;
            $after = get_declared_classes();
            $classNames = array_diff($after, $before);

            foreach ($classNames as $className) {
                $class = new ReflectionClass($className);
                if ($class->isSubclassOf($this->migrationBaseType)) {
                    self::$migrationClasses[$version] = $className;
                    $migraClass = $class;
                    break;
                }
            }

            if (!$migraClass) {
                $message = sprintf('Not found migration class in "%s"', $container);
                throw new Zeclib_MigrationException($message);
            }
        }

        $args = array(
            $version,
            $this->query
        );
        $migra = $migraClass->newInstanceArgs($args);

        return $migra;
    }

    /**
     * @param $version マイグレーションバージョン
     */
    public function markApplied($version)
    {
        $values = array(
            $this->systemColumn => $this->system,
            $this->versionColumn => $version,
        );
        $result = $this->query->insert($this->versionTable, $values);
        if (PEAR::isError($result)) {
            throw new Zeclib_MigrationException($result->getMessage(), $result->getCode());
        }
    }

    /**
     * @param $version マイグレーションバージョン
     */
    public function markReverted($version)
    {
        $where = "{$this->systemColumn} = ? AND {$this->versionColumn} = ?";
        $whereValues = array(
            $this->system,
            $version,
        );
        $result = $this->query->delete($this->versionTable, $where, $whereValues);
        if (PEAR::isError($result)) {
            throw new Zeclib_MigrationException($result->getMessage(), $result->getCode());
        }
    }

    /**
     * @param string $version
     */
    public function isAppliedVersion($version)
    {
        $where = "{$this->systemColumn} = ? AND {$this->versionColumn} = ?";
        $whereValues = array(
            $this->system,
            $version,
        );
        return $this->query->exists($this->versionTable, $where, $whereValues);
    }

    public function clear()
    {
        $where = "{$this->systemColumn} = ?";
        $whereValues = array(
            $this->system,
        );
        $result = $this->query->delete($this->versionTable, $where, $whereValues);
        if (PEAR::isError($result)) {
            throw new Zeclib_MigrationException($result->getMessage(), $result->getCode());
        }
    }
}
