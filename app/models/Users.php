<?php


namespace rsu\models;

use Phalcon\Mvc\Model;
use Phalcon\Security;

class Users extends Model
{
    public $id;

    public $surname;

    public $name;

    public $password;

    public $mail;

    public $role;

    public function initialize()
    {
        $this->setSource('users');
    }

    public function beforeCreate()
    {
        $security = new Security();
        $this->password = $security->hash($this->password);

    }

    public static function findByLogin($login)
    {
        return self::findFirst([
            'conditions' => 'mail = ?0',
            'bind'       => [$login]
        ]);

    }

    public function checkPassword($password)
    {
        /** @var $security \Phalcon\Security */
        $security = $this->getDI()->get('security');
        return $security->checkHash($password, $this->password);
    }

}