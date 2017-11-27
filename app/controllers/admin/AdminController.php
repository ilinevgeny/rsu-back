<?php


namespace rsu\controllers\admin;


class AdminController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setMainView('admin/main');
        $this->view->setVar('menu', true);
    }

    public function indexAction()
    {
        $this->view->pick("admin/index");
	    $this->view->setVar('statementActive', '');
	    $this->view->setVar('mainActive', 'active');
    }
}