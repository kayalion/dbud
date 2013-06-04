<?php

namespace dbud\controller;

use dbud\model\EnvironmentModel;
use dbud\model\ProjectModel;

use zibo\app\controller\AbstractController;
use zibo\app\view\BaseView;
use zibo\app\view\FormView;

use zibo\library\http\Response;
use zibo\library\orm\OrmManager;
use zibo\library\validation\exception\ValidationException;

/**
 * Controller of the environments
 */
class EnvironmentController extends AbstractController {

    /**
     * Action to show the detail of a environment
     * @param OrmManager $orm
     * @param string $project
     * @param string $slug
     * @return null
     */
    public function detailAction(OrmManager $orm, $project, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($slug);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $securityManager = $this->getSecurityManager();
        $deployCode = substr($securityManager->encrypt($environment->id), 0, 7);

        $serverModel = $orm->getDbudServerModel();
        $servers = $serverModel->getServersForEnvironment($environment->id);

        $translator = $this->getTranslator();

        $view = new BaseView('dbud/environment.detail');
        $view->setPageTitle($translator->translate('dbud.title.environment.detail'));
        $view->setPageSubTitle($environment->name);
        $view->set('project', $project);
        $view->set('environment', $environment);
        $view->set('deployCode', $deployCode);
        $view->set('servers', $servers);

        $this->response->setView($view);
    }

    /**
     * Action to add or edit a environment
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function formAction(OrmManager $orm, $project, $slug = null) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($slug);
        if ($slug) {
            if (!$environment || $environment->project != $project->id) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $environment = $environmentModel->createData();
        }

        if ($project->state != ProjectModel::STATE_CLONED) {
            $this->response->setRedirect($this->getUrl('dbud.project.detail', array('slug' => $project->slug)));

            return;
        }

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($environment);
        $formBuilder->addRow('name', 'string', array(
            'label' => $translator->translate('dbud.label.environment'),
            'description' => $translator->translate('dbud.label.environment.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('branch', 'select', array(
            'label' => $translator->translate('dbud.label.branch'),
            'description' => $translator->translate('dbud.label.branch.description'),
            'options' => $projectModel->getBranchesForProject($project),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('mode', 'select', array(
            'label' => $translator->translate('dbud.label.mode'),
            'description' => $translator->translate('dbud.label.mode.description'),
            'options' => array(
                EnvironmentModel::MODE_MANUAL => $translator->translate('dbud.mode.' . EnvironmentModel::MODE_MANUAL),
                EnvironmentModel::MODE_AUTOMATIC => $translator->translate('dbud.mode.' . EnvironmentModel::MODE_AUTOMATIC),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                if ($slug) {
                    $this->response->setRedirect($this->getUrl('dbud.environment.detail', array('project' => $project->slug, 'slug' => $slug)));
                } else {
                    $this->response->setRedirect($this->getUrl('dbud.project.detail', array('slug' => $project->slug)));
                }

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();
                $data->project = $project->id;

                $environmentModel->save($data);

                $this->response->setRedirect($this->getUrl('dbud.environment.detail', array('project' => $project->slug, 'slug' => $data->slug)));

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

                $form->setValidationException($exception);
            }
        }

        if ($slug) {
            $formAction = $this->getUrl('dbud.environment.edit.submit', array('project' => $project->slug, 'slug' => $slug));
        } else {
            $formAction = $this->getUrl('dbud.environment.add.submit', array('project' => $project->slug));
        }

        $view = new FormView($form->getFormView(), $formAction);

        if ($slug) {
            $view->setPageTitle($translator->translate('dbud.title.environment.edit'));
            $view->setPageSubTitle($environment->name);
        } else {
            $view->setPageTitle($translator->translate('dbud.title.environment.add'));
        }

        $this->response->setView($view);
    }

    /**
     * Action to manually queue deployment
     * @param OrmManager $orm
     * @param string $project
     * @param string $slug
     * @return null
     */
    public function deployAction(OrmManager $orm, $project, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($slug);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverOptions = array();
        $data = array('servers' => array());

        $serverModel = $orm->getDbudServerModel();
        $servers = $serverModel->getServersForEnvironment($environment->id);
        foreach ($servers as $server) {
            $serverOptions[$server->id] = $server->name;
            $data['servers'][$server->id] = $server->id;
        }

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($data);
        $formBuilder->addRow('servers', 'option', array(
            'label' => $translator->translate('dbud.label.servers.deploy'),
            'description' => $translator->translate('dbud.label.servers.deploy.description'),
            'options' => $serverOptions,
            'multiselect' => true,
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.environment.detail', array('project' => $project->slug, 'slug' => $slug)));

                return;
            }

            $queueModel = $orm->getDbudQueueModel();

            $data = $form->getData();
            foreach ($data['servers'] as $serverId) {
                $queueModel->queueDeploy($project, $environment, $servers[$serverId]);
            }

            $this->response->setRedirect($this->getUrl('dbud.environment.queue', array('project' => $project->slug, 'slug' => $slug)));

            return;
        }

        $formAction = $this->request->getUrl();

        $view = new FormView($form->getFormView(), $formAction, 'dbud/environment.deploy');
        $view->setPageTitle($translator->translate('dbud.title.environment.deploy'));
        $view->setPageSubTitle($environment->name);
        $view->set('project', $project);
        $view->set('environment', $environment);

        $this->response->setView($view);
    }

    /**
     * Action to automaticaly queue the deployment
     * @param OrmManager $orm
     * @param string $project
     * @param string $slug
     * @return null
     */
    public function autoDeployAction(OrmManager $orm, $project, $slug, $code) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($slug);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $securityManager = $this->getSecurityManager();
        $deployCode = substr($securityManager->encrypt($environment->id), 0, 7);

        if ($code != $deployCode || $environment->mode != EnvironmentModel::MODE_AUTOMATIC) {
            $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

            return;
        }

        $serverModel = $orm->getDbudServerModel();
        $queueModel = $orm->getDbudQueueModel();

        $servers = $serverModel->getServersForEnvironment($environment->id);
        foreach ($servers as $server) {
            $queueModel->queueDeploy($project, $environment, $server);
        }

        return;
    }

    /**
     * Action to show the queue of a environment
     * @param OrmManager $orm
     * @param string $project
     * @param string $slug
     * @return null
     */
    public function queueAction(OrmManager $orm, $project, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($slug);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $queue = $orm->getDbudQueueModel()->getQueueForEnvironment($environment->id);

        $translator = $this->getTranslator();

        $view = new BaseView('dbud/environment.queue');
        $view->setPageTitle($translator->translate('dbud.title.environment.queue'));
        $view->setPageSubTitle($environment->name);
        $view->set('project', $project);
        $view->set('environment', $environment);
        $view->set('queue', $queue);
        $view->addJavascript('js/dbud/queue.js');
        $view->addInlineJavascript('initializeQueue("' . $this->request->getUrl() . '", 5000);');

        $this->response->setView($view);
    }

    /**
     * Action to delete a environment
     * @param OrmManager $orm
     * @param string $project
     * @param string $slug
     * @return null
     */
    public function deleteAction(OrmManager $orm, $project, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($slug);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel->delete($environment);

        $this->response->setRedirect($this->getUrl('dbud.project.detail', array('slug' => $project->slug)));
    }

}