<?php

namespace rsu\controllers\admin;

/**
 * Класс объекта идентификации пользователя.
 */
class UserIdentity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var bool
     */
    protected $isAuthorized;

    /**
     * @var bool
     */
    protected $isMobile = false;

    /**
     * @var string
     */
    protected $role;

    /**
     * @param int    $id
     * @param string $role
     * @param bool   $isAuthorized
     */
    public function __construct($id, $role, $isAuthorized)
    {
        $this->id = (int) $id;
        $this->isAuthorized = (bool) $isAuthorized;
        $this->role = (string) $role;
    }

    /**
     * Возвращает идентификатор пользователя.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Возвращает true, если пользователь авторизован.
     * В противном случае возвращает false.
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->isAuthorized;
    }

    /**
     * Возвращает true, если пользователь использует мобильное устройство.
     * В противном случае возвращает false.
     * Если указан параметр $flag, то будет установлено соответствующее значение.
     *
     * @param bool|null $flag
     * @return bool
     */
    public function isMobile($flag = null)
    {
        if ($flag !== null) {
            $this->isMobile = (bool) $flag;
        }
        return $this->isMobile;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

}
