<?php

namespace rsu\Models;

use Phalcon\Mvc\Model;

class Houses extends Model
{
    public $id;

    public $city_id;

    public $street_id;

    public $number;

    public $photo_url;


    public static function findAll($offset, $limit)
    {
        return self::find([
            'limit'  => (int) $limit,
            'offset' => (int) $offset
        ]);
    }

    public function findById($id)
    {
        return self::findFirst([
            'conditions' => 'id = ?0',
            'bind'       => [$id]
        ]);
    }
}