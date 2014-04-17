<?php

/**
 * マイグレーションの適用をやり直します。
 *
 * @author zenith
 */
class Zeclib_Phing_MigrationRedoTask extends Task
{
    /**
     * @var PhingFile
     */
    protected $dataDir;

    /**
     * @var PhingFile
     */
    protected $htmlDir;

    /**
     * @var string
     */
    protected $versionTable;

    /**
     * @var string
     */
    protected $system;

    /**
     * @var string
     */
    protected $containerDir;

    /**
     * @var string
     */
    protected $version;

    public function main()
    {
        if ($this->dataDir === null || !$this->dataDir->isDirectory()) {
            $path = $this->dataDir ? $this->dataDir->getAbsolutePath() : '';
            $message = sprintf('Unable to apply migrations. EC-CUBE data directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->htmlDir === null || !$this->htmlDir->isDirectory()) {
            $path = $this->htmlDir ? $this->htmlDir->getAbsolutePath() : '';
            $message = sprintf('Unable to apply migrations. EC-CUBE html directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->containerDir === null || !$this->containerDir->isDirectory()) {
            $path = $this->containerDir ? $this->containerDir->getAbsolutePath() : '';
            $message = sprintf('Unable to apply migrations. container directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        $this->doRedo();
    }

    protected function doRedo()
    {
        $dataDir = $this->dataDir->getAbsolutePath();
        $htmlDir = $this->htmlDir->getAbsolutePath();
        define('HTML_REALDIR', rtrim(realpath($htmlDir), '/\\') . '/');

        require_once HTML_REALDIR . '/define.php';
        require_once HTML_REALDIR . HTML2DATA_DIR . '/require_base.php';

        $query = SC_Query_Ex::getSingletonInstance();
        $storage = new Zeclib_DefaultMigrationStorage($query, $this->system);
        $storage->versionTable = $this->versionTable;
        $storage->containerDirectories[] = $this->containerDir->getPath();

        $migrator = new Zeclib_Migrator($storage, $query);
        $migrator->logger = new Zeclib_Phing_TaskMigrationLogger($this);

        $migrations = array();
        $versions = preg_split('/[,\\s]+/', $this->version, 0, PREG_SPLIT_NO_EMPTY);
        foreach ($versions as $version) {
            try {
                $migrations[] = $migrator->loadMigration($version);
            } catch (Zeclib_MigrationException $e) {
                $message = $e->getMessage();
                $this->log($message, Zeclib_MigrationLogger::TYPE_WARNING);
            }
        }
        $num = $migrator->revert($migrations);
        $this->log(sprintf('%d migrations are reverted.', $num));

        $num = $migrator->apply($migrations);
        $this->log(sprintf('%d migrations are applicated.', $num));
    }

    public function setDataDir(PhingFile $dir)
    {
        $this->dataDir = $dir;
    }

    public function setHtmlDir(PhingFile $dir)
    {
        $this->htmlDir = $dir;
    }

    public function setVersionTable($table)
    {
        $this->versionTable = $table;
    }

    public function setSystem($system)
    {
        $this->system = $system;
    }

    public function setContainerDir(PhingFile $containerDir)
    {
        $this->containerDir = $containerDir;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}
