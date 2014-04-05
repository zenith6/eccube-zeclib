<?php

abstract class Zeclib_MigrationStorage
{
    /**
     * @var string
     */
    public $system;

    /**
     * @return array
     */
    abstract public function getAllVersions();

    /**
     * @return array
     */
    abstract public function getAppliedVersions();

    /**
     * @return array
     */
    public function getPendingVersions()
    {
        return array_diff($this->getAllVersions(), $this->getAppliedVersions());
    }

    /**
     * @param $version マイグレーションバージョン
     * @return Zeclib_Migration
     */
    abstract public function loadMigration($version);

    /**
     * @param $version マイグレーションバージョン
     */
    abstract public function markApplied($version);

    /**
     * @param $version マイグレーションバージョン
     */
    abstract public function markReverted($version);

    /**
     * @param $version マイグレーションバージョン
     */
    abstract public function isAppliedVersion($version);

    abstract public function clear();
}
