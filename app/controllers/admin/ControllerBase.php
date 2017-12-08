<?php

namespace rsu\controllers\admin;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;


class ControllerBase extends Controller
{
    protected $userIdentity = null;

    public function getUserIdentity()
    {
        if (!$this->userIdentity) {
            if (empty($_COOKIE[session_name()])) {
                $this->userIdentity = new UserIdentity(null, '', false);
            } else {
                if (session_status() != PHP_SESSION_ACTIVE) {
                    session_start();
                }
                if (isset($_SESSION['user']) && $_SESSION['user'] instanceof UserIdentity) {
                    $this->userIdentity = $_SESSION['user'];
                } else {
                    $this->sessionDestroy();
                    $this->userIdentity = new UserIdentity(null, '', false);
                }
            }
        }
        return $this->userIdentity;
    }

    public function sessionStart(UserIdentity $user)
    {
        if ($user->isAuthorized()) {
            if (session_status() != PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['user'] = $user;
        }
        $this->userIdentity = $user;
    }

    /**
     * Уничтожает текущую сессию авторизованного пользователя.
     */
    public function sessionDestroy()
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', 86400, $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
        session_destroy();
        $this->userIdentity = null;
    }

    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();
        $user = $this->getUserIdentity();
        $arrPages = [
            'index' => 'панели администрирования',
            'logout' => 'Этой странице',
            'list' => 'странице выписки',
            'edit' => 'странице редактирования'
        ];
//        print_r($controllerName . ' ' .$actionName); exit;
        $arrControllers = ['admin', 'statements', 'houses'];
        if (in_array($controllerName, $arrControllers) && !$user->isAuthorized()) {
            $this->flash->error ('Нет доступа к  ' . $arrPages[$actionName] );
            $dispatcher->forward([
                        'controller' => 'auth',
                        'action' => 'login'
                    ]);
        } elseif ($controllerName == 'auth' && !$user->isAuthorized()) {
	        $this->flash->error ('Пожалуйста, авторизуйтесь!');
        }
    }
}
