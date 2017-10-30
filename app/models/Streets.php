<?php

namespace rsu\Models;

use Phalcon\Mvc\Model;

class Streets extends Model
{
    public $id;

    public $name;

    public static function findById($id)
    {
        return self::findFirst([
            'conditions' => 'id = ?0',
            'bind'       => [$id]
        ]);

    }
}