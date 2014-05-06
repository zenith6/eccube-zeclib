<?php

/**
 * マイグレーションファイルを生成します。
 *
 * @author zenith
 */
class Zeclib_Phing_MigrationGenerateTask extends Task
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $containerDir;

    /**
     * @var string
     */
    protected $templateFile;

    /**
     * @var string
     */
    protected $containerName = '__TPL_VERSION_____TPL_NAME__Migration.php';

    /**
     * @var string
     */
    protected $baseClass = 'Zeclib_Migration';

    public function main()
    {
        if ($this->templateFile === null || !$this->templateFile->isFile()) {
            $path = $this->templateFile ? $this->templateFile->getAbsolutePath() : '';
            $message = sprintf('Unable to create migration. template file not available at "%s"', $path);
            throw new BuildException($message);
        }

        $templateFile = $this->templateFile->getAbsolutePath();
        $template = file_get_contents($templateFile);
        if ($template === false) {
            $message = sprintf('Unable to read template file at "%s"', $this->templateFile->getAbsolutePath());
            throw new BuildException($message);
        }

        if (!preg_match('/\\A[A-Za-z][0-9A-Za-z]*\\z/u', $this->name)) {
            $message = sprintf('Invalid name given. "%s"', $this->name);
            throw new BuildException($message);
        }

        if ($this->version == '') {
            $version = gmdate('YmdHis');
        } else {
            $version = $this->version;
        }

        if ($this->containerDir === null || !$this->containerDir->isDirectory()) {
            $path = $this->containerDir ? $this->containerDir->getAbsolutePath() : '';
            $message = sprintf('Unable to create migration. container directory not available at "%s"', $path);
            throw new BuildException($message);
        }

        $containerDir = $this->containerDir->getAbsolutePath();
        foreach (new DirectoryIterator($containerDir) as $file) {
            if (!$file->isFile() || $file->getExtension() == '.php') {
                continue;
            }

            list($exists) = (array)explode('_', $file->getBasename());
            if (strcmp($exists, $version) == 0) {
                $message = sprintf('Unable to create migration. same version already exists at "%s"', $file->getRealPath());
                throw new BuildException($message);
            }
        }

        $className = $this->toClassName($this->name);
        $replacements = array(
            '__TPL_VERSION__' => $version,
            '__TPL_NAME__' => $className,
            '__TPL_BASE__'  => $this->baseClass,
        );
        $container = strtr($template, $replacements);

        $fileName = $this->toContainerName($version, $this->name, $className);
        $dest = $containerDir . '/' . $fileName;
        if (file_put_contents($dest, $container, LOCK_EX) === false) {
            $message = sprintf('Failed to create migration at "%s"', $dest);
            throw new BuildException($message);
        }

        $log = sprintf('Create migration successfully. version %s at "%s"', $version, $dest);
        $this->log($log);
    }

    protected function toClassName($name) {
        return $name;
    }

    protected function toContainerName($version, $name, $className) {
        $replacements = array(
            '__TPL_VERSION__' => $version,
            '__TPL_NAME__' => $className,
            '__TPL_BASE__'  => $this->baseClass,
        );
        return strtr($this->containerName, $replacements);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function setContainerDir(PhingFile $containerDir)
    {
        $this->containerDir = $containerDir;
    }

    public function setTemplateFile(PhingFile $templateFile)
    {
        $this->templateFile = $templateFile;
    }

    public function setBaseClass($baseClass) {
        $this->baseClass = $baseClass;
    }

    public function setContainerName($name)
    {
        $this->containerName = $name;
    }
}
