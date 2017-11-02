<?php

namespace rsu\Models;

use Phalcon\Mvc\Model;

class Houses extends AbstractModel
{
    public $id;

    public $city_id;

    public $street_id;

    public $number;

    public $photo_url;

}