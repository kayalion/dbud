<?php

namespace dbud\controller;

use zibo\app\controller\AbstractController;
use zibo\app\view\BaseView;

use zibo\library\filesystem\File;
use zibo\library\http\Response;
use zibo\library\orm\OrmManager;

/**
 * Main controller
 */
class IndexController extends AbstractController {

    /**
     * Action to show the queue of a project
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function indexAction(OrmManager $orm) {
        $logModel = $orm->getDbudLogModel();
        $logs = $logModel->getLogs();

        $translator = $this->getTranslator();

        $view = new BaseView('dbud/activity');
        $view->setPageTitle($translator->translate('dbud.title.activity'));
        $view->set('logs', $logs);

        $this->response->setView($view);
    }

    /**
     * Action to obtain the SSH key of the system
     * @return null
     */
    public function sshKeyAction() {
        $publicKeyFile = $this->zibo->getParameter('dbud.ssh.key.public');
        if (!$publicKeyFile) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $publicKeyFile = new File($publicKeyFile);
        if (!$publicKeyFile->exists()) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $publicKey = $publicKeyFile->read();
        $translator = $this->getTranslator();

        $view = new BaseView('dbud/ssh.key');
        $view->setPageTitle($translator->translate('dbud.title.ssh.key'));
        $view->set('publicKey', $publicKey);

        $this->response->setView($view);
    }

}