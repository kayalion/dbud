<?php

namespace dbud\controller;

use dbud\model\data\RepositoryData;
use dbud\model\RepositoryModel;

use dbud\Module;

use zibo\app\controller\AbstractController as AppAbstractController;
use zibo\app\view\BaseView;

use zibo\library\http\Response;

/**
 * Controller of the repositories
 */
class AbstractController extends AppAbstractController {

    /**
     * Sets the view to working if the service is busy with the repository
     * @param dbud\model\data\RepositoryData $repository
     * @return boolean True if the working view is set, false otherwise
     */
    protected function setWorkingView(RepositoryData $repository) {
        if ($repository->state == Module::STATE_READY) {
            return false;
        }

        $view = $this->createView('dbud/repository.working', 'dbud.title.repository.detail', $repository);

        $this->response->setStatusCode(Response::STATUS_CODE_SERVICE_UNAVAILABLE);
        $this->response->setView($view);

        return true;
    }

    /**
     * Creates a repository view
     * @param string $template Path to the template
     * @param string $title Translation key of the title
     * @param dbud\model\data\RepositoryData $repository Repository
     * @return zibo\app\view\BaseView
     */
    protected function createView($template, $title, RepositoryData $repository = null) {
        $view = new BaseView($template);
        $view->addStyle('css/dbud.css');
        $view->setPageTitle($this->getTranslator()->translate($title));

        if ($repository) {
            $view->setPageSubTitle($repository->name);
            $view->set('repository', $repository);
        }

        return $view;
    }

}