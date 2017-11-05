<?php


namespace rsu\Service\Search;


use Phalcon\Mvc\User\Component;

class HousesSearch extends Component
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

    public function __construct()
    {
        $this->adapter = $this->getDI()->getShared('sphinx');
    }

    /**
     * @param string $query
     * @param int    $limit
     * @param int    $offset
     * @param float  $lat
     * @param float  $lng
     * @return int[]
     */
    public function find($query, $limit, $offset)
    {
        $result = false;
        $query = $this->prepareQuery($query);
        $where = $query !== '' ? 'WHERE MATCH(\'' . $query . '\')' : '';
        $sql = 'SELECT `id`  FROM  ' . self::INDEX_NAME . ' ' . $where . ' LIMIT ' . (int) $limit . ', ' . (int) $offset . ' OPTION  max_matches = ' . ($offset + $limit);
        $sql = mb_convert_encoding($sql, 'UTF-8');
        $result = $this->adapter->query($sql);

        if ($result != false) {
            $result = $result->fetchAll(\PDO::FETCH_COLUMN, 0);

            if (func_num_args() > 2) {
                $sql = 'SHOW META LIKE \'total_found\'';
                $total = $this->adapter->query($sql)->fetchColumn(1);
            }
        }
        return $result;
    }

    /**
     * @param string $query
     * @return int
     */
    public function count($query)
    {
        $query = $this->prepareQuery($query);

        $where = $query !== '' ? 'WHERE MATCH(\'' .  $query . '\')' : '';

        $sql = 'SELECT COUNT(*) FROM ' . self::INDEX_NAME . ' ' . $where;

        return (int) $this->adapter->query($sql)->fetchColumn(0);
    }

    protected function prepareQuery($query)
    {
        mb_regex_encoding('UTF-8');
        return trim(mb_eregi_replace('\W+', '*|', trim($query)), '|');
    }

}