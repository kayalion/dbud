<?php

namespace dbud\controller;

use zibo\app\controller\AbstractController;
use zibo\app\view\BaseView;
use zibo\app\view\FormView;

use zibo\library\http\Response;
use zibo\library\orm\OrmManager;
use zibo\library\validation\exception\ValidationException;

/**
 * Controller of the servers
 */
class ServerController extends AbstractController {

    /**
     * Action to show the detail of a server
     * @param OrmManager $orm
     * @param string $project
     * @param string $environment
     * @param string $slug
     * @return null
     */
    public function detailAction(OrmManager $orm, $project, $environment, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($environment);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverModel = $orm->getDbudServerModel();
        $server = $serverModel->getServerBySlug($slug);
        if (!$server || $server->environment != $environment->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $translator = $this->getTranslator();

        $view = new BaseView('dbud/server.detail');
        $view->setPageTitle($translator->translate('dbud.title.server.detail'));
        $view->setPageSubTitle($server->name);
        $view->set('project', $project);
        $view->set('environment', $environment);
        $view->set('server', $server);

        $this->response->setView($view);
    }

    /**
     * Action to add or edit a project
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function formAction(OrmManager $orm, $project, $environment, $slug = null) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($environment);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverModel = $orm->getDbudServerModel();
        if ($slug) {
            $server = $serverModel->getServerBySlug($slug);

            if (!$server || $server->environment != $environment->id) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $server = $serverModel->createData();
        }

        $translator = $this->getTranslator();

        $protocolOptions = array();

        $protocols = $this->zibo->getDependencies('dbud\\model\\protocol\\Protocol');
        foreach ($protocols as $name => $protocol) {
            $protocolOptions[$name] = $translator->translate('dbud.protocol.' . $name);
        }

        $protocol = null;
        if (isset($protocols[$server->protocol])) {
            $protocol = $protocols[$server->protocol];
        }

        $submitProtocol = $this->request->getBodyParameter('protocolSubmit');
        if ($submitProtocol) {
            $protocol = $protocols[$submitProtocol];

            $server->protocol = $submitProtocol;
        }

        $formBuilder = $this->createFormBuilder($server);
        $formBuilder->addRow('name', 'string', array(
            'label' => $translator->translate('dbud.label.server'),
            'description' => $translator->translate('dbud.label.server.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('protocol', 'select', array(
            'label' => $translator->translate('dbud.label.protocol'),
            'description' => $translator->translate('dbud.label.protocol.description'),
            'readonly' => $server->protocol ? true : false,
            'options' => $protocolOptions,
            'validators' => array(
                'required' => array(),
            ),
        ));

        if ($protocol) {
            $protocol->createForm($formBuilder, $translator);

            $formBuilder->addRow('protocolSubmit', 'hidden');
        }

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                if ($slug) {
                    $this->response->setRedirect($this->getUrl('dbud.server.detail', array('project' => $project->slug, 'environment' => $environment->slug, 'slug' => $slug)));
                } else {
                    $this->response->setRedirect($this->getUrl('dbud.environment.detail', array('project' => $project->slug, 'slug' => $environment->slug)));
                }

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();
                if ($protocol) {
                    $data->environment = $environment->id;

                    if ($data->newPassword) {
                        $securityManager = $this->getSecurityManager();
                        $data->remotePassword = $securityManager->encrypt($data->newPassword);
                    }

                    $protocol->processForm($data);

                    $serverModel->save($data);

                    $this->response->setRedirect($this->getUrl('dbud.server.detail', array('project' => $project->slug, 'environment' => $environment->slug, 'slug' => $data->slug)));

                    return;
                } elseif (isset($protocols[$data->protocol])) {
                    $protocol = $protocols[$data->protocol];
                    $protocol->createForm($formBuilder, $translator);

                    $data->protocolSubmit = $data->protocol;

                    $formBuilder->getRow('protocol')->setOption('readonly', true);
                    $formBuilder->addRow('protocolSubmit', 'hidden');
                    $formBuilder->setData($data);

                    $form = $formBuilder->build($this->request);
                }
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

                $this->addError(self::TRANSLATION_ERROR_VALIDATION);

                $form->setValidationException($exception);
            }
        }

        if ($slug) {
            $formAction = $this->getUrl('dbud.server.edit.submit', array('project' => $project->slug, 'environment' => $environment->slug, 'slug' => $slug));
        } else {
            $formAction = $this->getUrl('dbud.server.add.submit', array('project' => $project->slug, 'environment' => $environment->slug));
        }

        $module = $this->zibo->getDependency('dbud\Module');
        $publicKey = $module->getPublicKey($this->zibo);

        if ($server->protocol) {
            $template = 'dbud/server.form.' . $server->protocol;
        } else {
            $template = 'dbud/server.form';
        }

        $view = new FormView($form->getFormView(), $formAction, $template);
        $view->set('publicKey', $publicKey);

        if ($slug) {
            $view->setPageTitle($translator->translate('dbud.title.server.edit'));
            $view->setPageSubTitle($server->name);
        } else {
            $view->setPageTitle($translator->translate('dbud.title.server.add'));
        }

        $this->response->setView($view);
    }

    /**
     * Action to delete a server
     * @param OrmManager $orm
     * @param string $project
     * @param string $slug
     * @return null
     */
    public function deleteAction(OrmManager $orm, $project, $environment, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environment = $environmentModel->getEnvironmentBySlug($environment);
        if (!$environment || $environment->project != $project->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverModel = $orm->getDbudServerModel();
        $server = $serverModel->getServerBySlug($slug);
        if (!$server || $server->environment != $environment->id) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverModel->delete($server);

        $this->response->setRedirect($this->getUrl('dbud.environment.detail', array('project' => $project->slug, 'slug' => $environment->slug)));
    }

}