<?php

class Zeclib_DefaultMigrationStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    public $query;

    /**
     * @var array
     */
    public $pluginInfo;

    /**
     * @var string
     */
    public $system = 'default';

    /**
     * @var string
     */
    public static $containerBaseDir;

    protected $backupStaticAttributesBlacklist = array(
        array('Zeclib_DefaultMigrationStorage', 'migrationClasses'),
    );

    public static function setUpBeforeClass()
    {
        self::$containerBaseDir = ZECLIB_TEST_BASE . '/_files/migration';
    }

    public function setUp()
    {
        $methods = array(
            'begin',
            'commit',
            'rollback',
            'select',
            'insert',
            'delete',
            'inTransaction',
        );
        $this->query = $this->getMockBuilder('SC_Query_Ex')
            ->setMethods($methods)
            ->getMock();

        $this->pluginInfo = array(
            'plugin_code' => 'Test',
            'plugin_name' => 'Test',
        );
    }

    public function testGetAllVersions()
    {
        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->containerDirectories[] = self::$containerBaseDir . '/valid';
        $expected = array(
            '01',
            '02',
            '03',
        );
        $actual = $storage->getAllVersions();
        sort($actual, SORT_STRING);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Zeclib_MigrationException
     */
    public function testGetAllVersionsThrowExceptionIfDuplicatedVersionExists()
    {
        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->containerDirectories[] = self::$containerBaseDir . '/duplicate';
        $storage->getAllVersions();
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testGetAllVersionsThrowExceptionIfDirectoryNotExists()
    {
        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->containerDirectories[] = self::$containerBaseDir . '/not_exists';
        $storage->getAllVersions();
    }

    public function testGetAppliedVersions()
    {
        $rows = array(
            array('version' => '01'),
            array('version' => '02'),
        );
        $this->query->expects($this->once())
            ->method('select')
            ->willReturn($rows);

        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $expected = array(
            '01',
            '02',
        );
        $actual = $storage->getAppliedVersions();
        sort($actual, SORT_STRING);
        $this->assertEquals($expected, $actual);
    }

    public function testGetPendingVersions()
    {
        $storage = $this->getMockBuilder('Zeclib_DefaultMigrationStorage')
            ->setConstructorArgs(array($this->query, $this->system))
            ->setMethods(array('getAllVersions', 'getAppliedVersions'))
            ->getMock();

        $all = array(
            '01',
            '02',
            '03',
            '04',
            '05',
        );
        $stub = $storage->expects($this->once())
            ->method('getAllVersions')
            ->willReturn($all);

        $applieds = array(
            '01',
            '02',
            '04',
        );
        $stub = $storage->expects($this->once())
            ->method('getAppliedVersions')
            ->willReturn($applieds);

        $expected = array(
            '03',
            '05',
        );
        $actual = $storage->getPendingVersions();
        sort($actual, SORT_STRING);
        $this->assertEquals($expected, $actual);
    }

    public function testLoadMigration()
    {
        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->containerDirectories[] = self::$containerBaseDir . '/valid';

        $migra = $storage->loadMigration('01');
        $this->assertInstanceOf('Zeclib_Migration', $migra);
        $this->assertEquals('01', $migra->version);
    }

    /**
     * @expectedException Zeclib_MigrationException
     */
    public function testLoadMigrationThrowExpectionIfNotExists()
    {
        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->containerDirectories[] = self::$containerBaseDir . '/valid';
        $storage->loadMigration('not_exists');
    }

    public function testMarkApplied()
    {
        if (!class_exists('PEAR')) {
            $this->markTestSkipped('dependent on PEAR::isError()');
        }

        $this->query->expects($this->once())
            ->method('update');

        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->markApplied('01');
    }

    public function testMarkReverted()
    {
        if (!class_exists('PEAR')) {
            $this->markTestSkipped('dependent on PEAR::isError()');
        }

        $this->query->expects($this->once())
            ->method('delete');

        $storage = new Zeclib_DefaultMigrationStorage($this->query, $this->system);
        $storage->markReverted('01');
    }
}
