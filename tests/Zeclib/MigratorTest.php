<?php

class Zeclib_MigratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * SC_Query_Ex
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    public $query;

    /**
     * Zeclib_MigrationStorage
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    public $storage;

    public function setUp()
    {
        $this->storage = $this->getMockBuilder('Zeclib_MigrationStorage')
            ->getMock();

        $this->query = $this->getMockBuilder('SC_Query_Ex')
            ->setMethods(array('begin', 'commit', 'rollback', 'inTransaction'))
            ->getMock();
    }

    public function testApply()
    {
        $migrator = new Zeclib_Migrator($this->storage, $this->query);

        $migras = array();
        $migra01 = $this->getMockForAbstractClass('Zeclib_Migration', array('01', $this->query));
        $migra01->expects($this->once())->method('up');
        $migras[] = $migra01;

        $migra02 = $this->getMockForAbstractClass('Zeclib_Migration', array('02', $this->query));
        $migra02->expects($this->once())->method('up');
        $migras[] = $migra02;

        $migra03 = $this->getMockForAbstractClass('Zeclib_Migration', array('03', $this->query));
        $migra03->expects($this->once())->method('up');
        $migras[] = $migra03;

        $migrator->apply($migras);
        foreach ($migras as $migra) {
            $this->assertTrue($migra->applied, 'Not work migration: ' . $migra->version);
        }
    }

    public function testThrowExceptionIfApplyIsAborted()
    {
        $migrator = new Zeclib_Migrator($this->storage, $this->query);

        $migras = array();
        $migra01 = $this->getMockForAbstractClass('Zeclib_Migration', array('01', $this->query));
        $migra01->expects($this->once())->method('up');
        $migras[] = $migra01;

        $migra02 = $this->getMockForAbstractClass('Zeclib_Migration', array('02', $this->query));
        $migra02->expects($this->once())->method('up')
            ->willThrowException(new Zeclib_MigrationException());
        $migras[] = $migra02;

        $migra03 = $this->getMockForAbstractClass('Zeclib_Migration', array('03', $this->query));
        $migra03->expects($this->never())->method('up');
        $migras[] = $migra03;

        try {
            $migrator->apply($migras);
        } catch (Zeclib_MigrationException $e) {
        }
    }

    public function testRevert()
    {
        $this->storage->expects($this->any())
            ->method('isAppliedVersion')
            ->willReturn(true);

        $migrator = new Zeclib_Migrator($this->storage, $this->query);

        $migras = array();
        $migra01 = $this->getMockForAbstractClass('Zeclib_Migration', array('01', $this->query));
        $migra01->expects($this->once())->method('down');
        $migras[] = $migra01;

        $migra02 = $this->getMockForAbstractClass('Zeclib_Migration', array('02', $this->query));
        $migra02->expects($this->once())->method('down');
        $migras[] = $migra02;

        $migra03 = $this->getMockForAbstractClass('Zeclib_Migration', array('03', $this->query));
        $migra03->expects($this->once())->method('down');
        $migras[] = $migra03;

        $migrator->revert($migras);
        foreach ($migras as $migra) {
            $this->assertFalse($migra->applied, 'Not work migration: ' . $migra->version);
        }
    }

    public function testTransactionCommitIfApplyIsCompleted()
    {
        $this->query->expects($this->once())->method('begin');
        $this->query->expects($this->once())->method('commit');

        $migrator = new Zeclib_Migrator($this->storage, $this->query);

        $migras = array();
        $migra01 = $this->getMockForAbstractClass('Zeclib_Migration', array('01', $this->query));
        $migras[] = $migra01;

        $migrator->apply($migras);
    }

    public function testTransactionRollbackIfApplyIsIncompleted()
    {
        $this->query->expects($this->once())->method('begin');
        $this->query->expects($this->once())->method('rollback');
        $this->query->expects($this->once())->method('inTransaction')->willReturn(true);

        $migrator = new Zeclib_Migrator($this->storage, $this->query);

        $migras = array();
        $migra01 = $this->getMockForAbstractClass('Zeclib_Migration', array('01', $this->query));
        $migra01->expects($this->once())
            ->method('up')
            ->willThrowException(new Zeclib_MigrationException());
        $migras[] = $migra01;

        try {
            $migrator->apply($migras);
        } catch (Zeclib_MigrationException $e) {
        }
    }

    protected function isIncludedVersion($version, $from, $to)
    {
        return ($from === null || strcmp($version, $from) >= 0)
            && ($to === null || strcmp($version, $to) <= 0);
    }

    public function provideUpPattern()
    {
        $versions = array(
            '01',
            '02',
            '03',
            '04',
            '05',
        );

        return array(
            array($versions, null, null),
            array($versions, '02', null),
            array($versions, null, '04'),
            array($versions, '02', '04'),
            array(array(), null, null),
            array(array(), '02', '04'),
        );
    }

    /**
     * @dataProvider provideUpPattern
     */
    public function testUp($versions, $from, $to)
    {
        $this->storage->expects($this->once())
            ->method('getPendingVersions')
            ->will($this->returnValue($versions));

        $returnValues = array();
        foreach ($versions as $index => $version) {
            $migra = $this->getMockForAbstractClass('Zeclib_Migration', array($version, $this->query));
            $migra->applied = false;

            $cond = $this->isIncludedVersion($version, $from, $to) ? $this->once() : $this->never();
            $migra->expects($cond)->method('up');

            $returnValues[] = array(
                $version,
                $migra
            );
        }
        $this->storage->expects($this->any())
            ->method('loadMigration')
            ->will($this->returnValueMap($returnValues));

        $migrator = new Zeclib_Migrator($this->storage, $this->query);
        $migrator->up($from, $to);

        foreach ($returnValues as $values) {
            list($version, $migra) = $values;
            $expected = $this->isIncludedVersion($version, $from, $to);
            $this->assertEquals($expected, $migra->applied, 'Migration version:' . $version);
        }
    }
    public function provideDownPattern()
    {
        $versions = array(
            '01',
            '02',
            '03',
            '04',
            '05',
        );

        return array(
            array($versions, null, null),
            array($versions, '02', null),
            array($versions, null, '04'),
            array($versions, '02', '04'),
            array(array(), null, null),
            array(array(), '02', '04'),
        );
    }

    /**
     * @dataProvider provideDownPattern
     */
    public function testDown($versions, $from, $to)
    {
        $this->storage->expects($this->once())
            ->method('getAppliedVersions')
            ->will($this->returnValue($versions));

        $this->storage->expects($this->any())
            ->method('isAppliedVersion')
            ->willReturn(true);

        $returnValues = array();
        foreach ($versions as $index => $version) {
            $migra = $this->getMockForAbstractClass('Zeclib_Migration', array($version, $this->query));
            $migra->applied = true;

            $cond = $this->isIncludedVersion($version, $from, $to) ? $this->once() : $this->never();
            $migra->expects($cond)->method('down');

            $returnValues[] = array(
                $version,
                $migra
            );
        }
        $this->storage->expects($this->any())
            ->method('loadMigration')
            ->will($this->returnValueMap($returnValues));

        $migrator = new Zeclib_Migrator($this->storage, $this->query);
        $migrator->down($from, $to);

        foreach ($returnValues as $values) {
            list($version, $migra) = $values;
            $expected = !$this->isIncludedVersion($version, $from, $to);
            $this->assertEquals($expected, $migra->applied, 'Migration version:' . $version);
        }
    }

    public function testMarkAppliedAll()
    {
        $this->storage->expects($this->exactly(5))
            ->method('markApplied');

        $versions = array(
            '01',
            '02',
            '03',
            '04',
            '05',
        );
        $this->storage->expects($this->once())
            ->method('getAllVersions')
            ->willReturn($versions);

        $migrator = new Zeclib_Migrator($this->storage, $this->query);
        $migrator->markAppliedAll();

    }
}
