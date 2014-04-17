<?php

/**
 * マイグレーションシステムを指定した EC-CUBE へ組み込みます。
 *
 * @author zenith
 */
class Zeclib_Phing_MigrationSetupTask extends Task
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

    public function main()
    {
        if ($this->dataDir === null || !$this->dataDir->isDirectory()) {
            $path = $this->dataDir ? $this->dataDir->getAbsolutePath() : '';
            $message = sprintf('Unable to setup migrations. EC-CUBE data directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        if ($this->htmlDir === null || !$this->htmlDir->isDirectory()) {
            $path = $this->htmlDir ? $this->htmlDir->getAbsolutePath() : '';
            $message = sprintf('Unable to setup migrations. EC-CUBE html directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        $this->doSetup();
    }

    protected function doSetup()
    {
        $dataDir = $this->dataDir->getAbsolutePath();
        $htmlDir = $this->htmlDir->getAbsolutePath();
        define('HTML_REALDIR', rtrim(realpath($htmlDir), '/\\') . '/');

        require_once HTML_REALDIR . '/define.php';
        require_once HTML_REALDIR . HTML2DATA_DIR . '/require_base.php';

        $query = SC_Query_Ex::getSingletonInstance();
        $storage = new Zeclib_DefaultMigrationStorage($query, $this->system);
        $storage->versionTable = $this->versionTable;
        $storage->setup();

        $this->log(sprintf('Setup migration database successfully: %s', $this->versionTable));
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
}
