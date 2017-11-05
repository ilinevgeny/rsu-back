<?php

namespace rsu\Service\Search;

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\User\Component;
use rsu\Models\Houses;
use rsu\models\Regions;
use rsu\models\Cities;
use rsu\Models\Streets;

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

    protected function prepareIndexValues($house)
    {
        $street = Streets::findById($house->street_id);
        $city = Cities::findById($house->city_id);
        $region = Regions::findById($city->region_id);

        $result = [
            'id' => $house->id,
            'sity' => $this->adapter->quote($city->name),
            'street' => $this->adapter->quote($street->name),
            'number' => $this->adapter->quote($house->number),
            'fid' => $house->id,
            ];
        return  $result;
    }

    public function __construct()
    {
        $this->adapter = $this->getDI()->getShared('sphinx');
    }

    public function insertHouses(\Traversable $houses)
    {
        $sql = '';
        $vals = $strVals = [];
        foreach ($houses as $house) {
            $vals = $this->prepareIndexValues($house);
            $strVals[] = implode(',', $vals);
        }
        if (!empty($strVals)) {
            $sql = sprintf('INSERT INTO %s  VALUES (%s)', self::INDEX_NAME, implode('),(', $strVals));
            $sql = mb_convert_encoding($sql, 'UTF-8');
            $this->execQuery($sql);
        }
        return count($strVals);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function truncate()
    {
        $this->adapter->exec('TRUNCATE RTINDEX ' . self::INDEX_NAME);
    }

    protected function prepareQuery($query)
    {
        mb_regex_encoding('UTF-8');
        return trim(mb_eregi_replace('\W+', '|', trim($query)), '|');
    }
}