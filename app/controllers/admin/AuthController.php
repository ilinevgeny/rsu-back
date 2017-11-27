<?php

namespace rsu\controllers\admin;

use rsu\models\Users;

class AuthController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setMainView('admin/main');
        $this->view->setVar('menu', false);
    }

    public function indexAction()
    {

    }
    public function loginAction()
    {
        $error = '';
        if ($this->request->isPost()) {
            $login = trim($this->request->getPost('login', null, ''));
            $password = trim($this->request->getPost('password', null, ''));

            if (!empty($login) && !empty($password)) {
                $user = Users::findByLogin($login);
                if (!empty($user)) {
                    if ($user->checkPassword($password)) {
//                        if ($user->status == Users::STATUS_ACTIVE) {
                            $userIdentity = new UserIdentity($user->id, $user->role, true);
                              $this->sessionStart($userIdentity);
                            return $this->response->redirect('/admin/statements/');
//                        } elseif ($user->status == $user::STATUS_NEW) {
//                            $error = 'Необходимо активировать учетную запись пользователя.';
//                        } else {
//                            $error = 'Пользователь заблокирован.';
//                        }
                    }
                }
            }
            if (empty($error)) {
                $error = 'Указанный логин или пароль не верен.';
            }
        }
        $this->view->setVar('errorMessage', $error);
    }

    public function logoutAction()
    {
        $this->sessionDestroy();
        $this->response->redirect('/admin/auth/');
    }
}