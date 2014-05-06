<?php

class Zeclib_Phing_MigrationGenerateTaskTest extends Zeclib_Phing_TaskTestBase
{
    protected function setUp()
    {
        parent::setUp();

        $this->configureProject(ZECLIB_TEST_FILES_DIR . '/phing/migration_generate.xml');
    }

    public function testGenerate()
    {
        $this->project->setUserProperty('name', 'CreateTable');
        $this->project->setUserProperty('version', '123');
        $containerDir = $this->createTempDir();
        $this->project->setUserProperty('container_dir', $containerDir);
        $this->executeTarget('generate');

        $container = $containerDir . '/123_CreateTableMigration.php';
        $this->assertFileExists($container);

        $expected = file_get_contents(ZECLIB_TEST_FILES_DIR . '/phing/migration/expected_123_CreateTableMyMigration.php');
        $contents = file_get_contents($container);
        $this->assertEquals($expected, $contents);
    }
}
