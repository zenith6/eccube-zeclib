<?php

/**
 * マイグレーションを全て適用済みの状態にします。
 *
 * @author zenith
 */
class Zeclib_Phing_MigrationMarkTask extends Task
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

    public function main()
    {
        if ($this->dataDir === null || !$this->dataDir->isDirectory()) {
            $path = $this->dataDir ? $this->dataDir->getAbsolutePath() : '';
            $message = sprintf('Unable to mark applied. EC-CUBE data directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->htmlDir === null || !$this->htmlDir->isDirectory()) {
            $path = $this->htmlDir ? $this->htmlDir->getAbsolutePath() : '';
            $message = sprintf('Unable to mark applied. EC-CUBE html directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->containerDir === null || !$this->containerDir->isDirectory()) {
            $path = $this->containerDir ? $this->containerDir->getAbsolutePath() : '';
            $message = sprintf('Unable to mark applied. container directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        $this->doMark();
    }

    protected function doMark()
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

        $migrator->clear();
        $this->log(sprintf('All migrations are removed.'));

        $migrator->markAppliedAll();
        $this->log(sprintf('All migrations are mark with applied.'));
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
}
