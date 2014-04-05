<?php

/**
 * @author Seiji Nitta
 */
class Zeclib_Phing_PluginInfoGetTask extends Task
{
    /**
     * @var PhingFile
     */
    protected $plugin;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $to;

    const INFO_FILE = 'plugin_info.php';

    public function main()
    {
        $plugin = $this->plugin->getAbsolutePath();
        $infoFile = $plugin . '/' . self::INFO_FILE;
        if (!file_exists($infoFile)) {
            $message = sprintf('Unable to read plugin_info.php file at "%s"', $plugin);
            throw new BuildException($message);
        }

        require_once $infoFile;
        $class = new ReflectionClass('plugin_info');
        try {
            $value = $class->getStaticPropertyValue($this->key);
        } catch (Exception $e) {
            $message = sprintf('Unable to read property with "%s"', $this->key);
            throw new BuildException($message, $e);
        }

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $value = json_encode($value);
        }

        if ($this->to != '') {
            $this->project->setProperty($this->to, $value);
        } else {
            $this->log($value);
        }
    }

    public function setPlugin(PhingFile $plugin)
    {
        $this->plugin = $plugin;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setTo($to)
    {
        $this->to = $to;
    }
}
