<?php


namespace rsu\models;

use Phalcon\Mvc\Model;

abstract class AbstractModel extends Model
{
    /**
     * @param $id
     * @param int|string|array $params
     * @return static
     */
    public static function findById($id)
    {
        $model = parent::findFirst([
            'conditions' => 'id = ?0',
            'bind'       => [$id]
        ]);

        return $model;
    }
}