<?php


namespace rsu\models;

use Phalcon\Mvc\Model;

abstract class AbstractModel extends Model
{
    /**
     * @param $id
     * @param int|string|array $params
     * @return self|static
     */
    public static function findById($id)
    {
        $model = parent::findFirst([
            'conditions' => 'id = ?0',
            'bind'       => [$id]
        ]);

        return $model;
    }

    /**
     * @param $offset
     * @param $limit
     * @return self|static
     */
    public static function findAll($offset, $limit)
    {
        return self::find([
            'limit'  => (int) $limit,
            'offset' => (int) $offset
        ]);
    }
}