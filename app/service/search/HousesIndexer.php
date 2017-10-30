<?php

namespace rsu\Service\Search;

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\User\Component;
use rsu\Models\Houses;

class HousesIndexer extends Component
{
    const INDEX_NAME = 'rsu_houses_address';

    /**
     * @var \PDO
     */
    protected $adapter;

    /**
     * @param string $sql
     * @return void
     * @throws \Exception
     */
    protected function execQuery($sql)
    {
        $pdo = $this->adapter;
        if (!$pdo->exec($sql)) {
            throw new \Exception($pdo->errorInfo()[2], $pdo->errorCode());
        }
    }

    protected function prepareIndexValues($houses)
    {

    }

    public function __construct()
    {
        $this->adapter = $this->getDI()->getShared('sphinx');
    }

    public function insertHouses(\Traversable $houses)
    {
        $sql = '';
        foreach ($houses as $house) {
            $sql = "INSERT INTO `rsu_houses_address` VALUES ($house->id, '" . $house->number . "')";
            $this->execQuery($sql);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function truncate()
    {
        $this->adapter->exec('TRUNCATE RTINDEX ' . self::INDEX_NAME);
    }
}