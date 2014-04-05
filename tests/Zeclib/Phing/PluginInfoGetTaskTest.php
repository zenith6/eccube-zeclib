<?php

class Zeclib_Phing_PluginInfoGetTaskTest extends Zeclib_Phing_TaskTestBase
{
    protected function setUp()
    {
        parent::setUp();

        $this->configureProject(ZECLIB_TEST_FILES_DIR . '/phing/plugin_info.xml');
    }

    /**
     * @expectedException BuildException
     */
    public function testThrowsExceptionIfPluginNotFound()
    {
        $this->executeTarget('missing-plugin');
    }

    /**
     * @expectedException BuildException
     */
    public function testThrowsExceptionIfPropertyNotFound()
    {
        $this->executeTarget('missing-property');
    }

    public function testToLog()
    {
        $this->expectLog('to-log', 'plugin_name');
    }

    public function testToProperty()
    {
        $this->executeTarget('to-property');
        $this->assertEquals('plugin_name', $this->project->getProperty('plugin_name'));
    }

    public function provideValidProperties()
    {
        return array(
            array('PLUGIN_NAME', 'plugin_name'),
            array('PLUGIN_CODE', 'plugin_code'),
            array('PLUGIN_VERSION', '1.0.0'),
            array('PLUGIN_SITE_URL', 'http://www.example.com/plugin/'),
            array('DESCRIPTION', 'This is test plugin.'),
            array('AUTHOR', 'author'),
            array('AUTHOR_SITE_URL', 'http://www.example.com/author/'),
            array('LICENSE', 'license'),
            array('CLASS_NAME', 'class_name'),
            array('COMPLIANT_VERSION', '2.13.1'),
            array('HOOK_POINTS', '["hook1","hook2"]'),
        );
    }

    /**
     * @dataProvider provideValidProperties
     */
    public function testGetProperty($key, $value)
    {
        $this->project->setUserProperty('key', $key);
        $this->executeTarget('get-property');
        $this->assertEquals($value, $this->project->getProperty('value'));
    }
}
