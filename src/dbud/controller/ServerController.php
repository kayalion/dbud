<?php

namespace dbud\controller;

use dbud\model\RepositoryModel;
use dbud\model\ServerModel;

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
     * Action to add or edit a project
     * @param OrmManager $orm
     * @param string $repository
     * @param string $branch
     * @param string $server
     * @return null
     */
    public function formAction(OrmManager $orm, $repository, $branch, $server = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverModel = $orm->getDbudServerModel();
        if ($server) {
            $server = $serverModel->getServerBySlug($server);

            if (!$server || $server->repository != $repository->id || $server->branch != $branch) {
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
            'label' => $translator->translate('dbud.label.name'),
            'description' => $translator->translate('dbud.label.server.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('mode', 'select', array(
            'label' => $translator->translate('dbud.label.mode'),
            'description' => $translator->translate('dbud.label.mode.description'),
            'options' => array(
                ServerModel::MODE_MANUAL => $translator->translate('dbud.mode.' . ServerModel::MODE_MANUAL),
                ServerModel::MODE_AUTOMATIC => $translator->translate('dbud.mode.' . ServerModel::MODE_AUTOMATIC),
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
                $this->response->setRedirect($this->getUrl('dbud.repository.deployment', array('repository' => $repository->slug, 'branch' => $branch)));

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();
                if ($protocol) {
                    $data->repository = $repository->id;
                    $data->branch = $branch;

                    if ($data->newPassword) {
                        $securityManager = $this->getSecurityManager();
                        $data->remotePassword = $securityManager->encrypt($data->newPassword);
                    }

                    $protocol->processForm($data);

                    $serverModel->save($data);

                    $this->response->setRedirect($this->getUrl('dbud.repository.deployment', array('repository' => $repository->slug, 'branch' => $branch)));

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

        if ($server->id) {
            $formAction = $this->getUrl('dbud.server.edit.submit', array('repository' => $repository->slug, 'branch' => $branch, 'server' => $server->slug));
        } else {
            $formAction = $this->getUrl('dbud.server.add.submit', array('repository' => $repository->slug, 'branch' => $branch));
        }

        $module = $this->zibo->getDependency('dbud\Module');
        $publicKey = $module->getPublicKey($this->zibo);

        $git = $repositoryModel->getGitRepository($repository, $branch);
        $branches = $git->getBranches();

        if ($server->protocol) {
            $template = 'dbud/server.form.' . $server->protocol;
        } else {
            $template = 'dbud/server.form';
        }

        $view = new FormView($form->getFormView(), $formAction, $template);
        $view->setPageTitle($translator->translate('dbud.title.repository.detail'));
        $view->setPageSubTitle($repository->name);
        $view->addStyle('css/dbud.css');
        $view->set('repository', $repository);
        $view->set('branches', $branches);
        $view->set('branch', $branch);
        $view->set('server', $server);
        $view->set('publicKey', $publicKey);

        $this->response->setView($view);
    }

    /**
     * Action to delete a server
     * @param OrmManager $orm
     * @param string $repository
     * @param string $branch
     * @param string $server
     * @return null
     */
    public function deleteAction(OrmManager $orm, $repository, $branch, $server) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverModel = $orm->getDbudServerModel();
        $server = $serverModel->getServerBySlug($server);
        if (!$server || $server->repository != $repository->id || $server->branch != $branch) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->request->isPost()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.repository.deployment', array('repository' => $repository->slug, 'branch' => $branch)));

                return;
            }

            $serverModel->delete($server);

            $this->response->setRedirect($this->getUrl('dbud.repository.deployment', array('repository' => $repository->slug, 'branch' => $branch)));

            return;
        }

        $view = $this->createView('dbud/server.delete', 'dbud.title.repository.detail', $repository);
        $view->set('branch', $branch);
        $view->set('server', $server);

        $this->response->setView($view);
    }

}