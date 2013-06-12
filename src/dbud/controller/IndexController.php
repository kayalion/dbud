<?php

namespace dbud\controller;

use zibo\app\view\BaseView;

use zibo\library\filesystem\File;
use zibo\library\http\Response;
use zibo\library\orm\OrmManager;

/**
 * Main controller
 */
class IndexController extends AbstractController {

    /**
     * Action to redirect to the start page
     * @return null
     */
    public function indexAction() {
        $this->response->setRedirect($this->getUrl('dbud.activity'));
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

        $view = $this->createView('dbud/ssh.key', 'dbud.title.ssh.key');
        $view->set('publicKey', $publicKey);

        $this->response->setView($view);
    }

}