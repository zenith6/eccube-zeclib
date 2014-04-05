<?php

/**
 * マイグレーションシステムを指定した EC-CUBE へ組み込みます。
 *
 * @author zenith
 */
class Zeclib_Phing_MigrationSetupTask extends Task
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $versionTable;

    /**
     * @var PhingFile
     */
    protected $dataDir;

    /**
     * @var PhingFile
     */
    protected $htmlDir;

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

        $this->setupDatabase();
    }

    protected function setupDatabase()
    {
        $dataDir = $this->dataDir->getAbsolutePath();
        $htmlDir = $this->htmlDir->getAbsolutePath();
        define('HTML_REALDIR', rtrim(realpath($htmlDir), '/\\') . '/');

        require_once HTML_REALDIR . '/define.php';
        require_once HTML_REALDIR . HTML2DATA_DIR . '/require_base.php';

        $query = SC_Query_Ex::getSingletonInstance();
        $mdb2 = $query->conn;
        $mdb2->loadModule('Manager');

        $def = array(
            'system' => array(
                'type' => 'text',
                'length' => 255,
                'notnull' => 1,
            ),
            'version' => array(
                'type' => 'text',
                'length' => 255,
                'notnull' => 1,
            ),
        );
        $result = $mdb2->createTable($this->versionTable, $def);
        if (PEAR::isError($result)) {
            throw new BuildException($result->getMessage());
        }

        $def = array(
            'primary' => true,
            'fields'  => array(
                'system' => array(),
                'version' => array(),
            ),
        );
        $name = $this->versionTable . '_primary';
        $result = $mdb2->createConstraint($this->versionTable, $name, $def);
        if (PEAR::isError($result)) {
            throw new BuildException($result->getMessage());
        }
    }

    public function setVersionTable($table)
    {
        $this->versionTable = $table;
    }

    public function setDataDir(PhingFile $dir)
    {
        $this->dataDir = $dir;
    }

    public function setHtmlDir(PhingFile $dir)
    {
        $this->htmlDir = $dir;
    }
}
