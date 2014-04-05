<?php

/**
 * マイグレーションを取り消します。
 *
 * @author zenith
 */
class Zeclib_Phing_MigrationDownTask extends Task
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
    protected $containerDir;

    /**
     * @var string
     */
    protected $system;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $to;

    /**
     * @var string
     */
    protected $ignoreMissing;

    public function main()
    {
        if ($this->dataDir === null || !$this->dataDir->isDirectory()) {
            $path = $this->dataDir ? $this->dataDir->getAbsolutePath() : '';
            $message = sprintf('Unable to revert migrations. EC-CUBE data directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->htmlDir === null || !$this->htmlDir->isDirectory()) {
            $path = $this->htmlDir ? $this->htmlDir->getAbsolutePath() : '';
            $message = sprintf('Unable to revert migrations. EC-CUBE html directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->containerDir === null || !$this->containerDir->isDirectory()) {
            $path = $this->containerDir ? $this->containerDir->getAbsolutePath() : '';
            $message = sprintf('Unable to revert migrations. container directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        $this->doDown();
    }

    protected function doDown()
    {
        $dataDir = $this->dataDir->getAbsolutePath();
        $htmlDir = $this->htmlDir->getAbsolutePath();
        define('HTML_REALDIR', rtrim(realpath($htmlDir), '/\\') . '/');

        require_once HTML_REALDIR . '/define.php';
        require_once HTML_REALDIR . HTML2DATA_DIR . '/require_base.php';

        $query = SC_Query_Ex::getSingletonInstance();
        $storage = new Zeclib_DefaultMigrationStorage($query, $this->system);
        $storage->containerDirectories[] = $this->containerDir->getPath();

        $migrator = new Zeclib_Migrator($storage, $query);
        $migrator->logger = new Zeclib_Phing_TaskMigrationLogger($this);

        $from = $this->from == '' ? null : $this->from;
        $to = $this->to == '' ? null : $this->to;
        $ignoreMissing = strncmp(strtolower($this->ignoreMissing), 'y', 1) == 0;
        $num = $migrator->down($from, $to, $ignoreMissing);

        $this->log(sprintf('%d migrations are reverted.', $num));
    }

    public function setDataDir(PhingFile $dir)
    {
        $this->dataDir = $dir;
    }

    public function setHtmlDir(PhingFile $dir)
    {
        $this->htmlDir = $dir;
    }

    public function setContainerDir(PhingFile $containerDir)
    {
        $this->containerDir = $containerDir;
    }

    public function setSystem($system)
    {
        $this->system = $system;
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function setTo($to)
    {
        $this->to = $to;
    }

    public function setIgnoreMissing($ignore)
    {
        $this->ignoreMissing = $ignore;
    }
}
