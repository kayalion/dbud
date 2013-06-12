<?php

namespace dbud\controller;

use dbud\Module;

use zibo\app\view\BaseView;
use zibo\app\view\FormView;

use zibo\library\http\Response;
use zibo\library\orm\OrmManager;
use zibo\library\validation\exception\ValidationException;

/**
 * Controller of the builders
 */
class BuilderController extends AbstractController {

    /**
     * Action to add or edit a project
     * @param OrmManager $orm
     * @param string $repository
     * @param string $branch
     * @param string $builder
     * @return null
     */
    public function formAction(OrmManager $orm, $repository, $branch, $builder = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $builderModel = $orm->getDbudBuilderModel();
        if ($builder) {
            $builder = $builderModel->getBuilderBySlug($builder);

            if (!$builder || $builder->repository != $repository->id || $builder->branch != $branch) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $builder = $builderModel->createData();
        }

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($builder);
        $formBuilder->addRow('name', 'string', array(
            'label' => $translator->translate('dbud.label.name'),
            'description' => $translator->translate('dbud.label.builder.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('copyRepository', 'checkbox', array(
            'label' => $translator->translate('dbud.label.repository.copy'),
            'description' => $translator->translate('dbud.label.repository.copy.description'),
        ));
        $formBuilder->addRow('script', 'text', array(
            'label' => $translator->translate('dbud.label.commands.build'),
            'description' => $translator->translate('dbud.label.commands.build.description'),
            'filters' => array(
                'trim' => array('trim.lines' => true, 'trim.empty' => true),
            ),
            'validators' => array(
                'required' => array(),
            ),
            'attributes' => array(
                'class' => 'console',
                'rows' => 10,
            ),
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.repository.integration', array('repository' => $repository->slug, 'branch' => $branch)));

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();
                $data->repository = $repository->id;
                $data->branch = $branch;
                $data->state = Module::STATE_NEW;
                $data->revision = '';

                $builderModel->save($data);

                $this->response->setRedirect($this->getUrl('dbud.repository.integration', array('repository' => $repository->slug, 'branch' => $branch)));

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

                $this->addError(self::TRANSLATION_ERROR_VALIDATION);

                $form->setValidationException($exception);
            }
        }

        if ($builder->id) {
            $formAction = $this->getUrl('dbud.builder.edit.submit', array('repository' => $repository->slug, 'branch' => $branch, 'builder' => $builder->slug));
        } else {
            $formAction = $this->getUrl('dbud.builder.add.submit', array('repository' => $repository->slug, 'branch' => $branch));
        }

        $git = $repositoryModel->getGitRepository($repository, $branch);
        $branches = $git->getBranches();

        $view = new FormView($form->getFormView(), $formAction, 'dbud/builder.form');
        $view->setPageTitle($translator->translate('dbud.title.repository.detail'));
        $view->setPageSubTitle($repository->name);
        $view->addStyle('css/dbud.css');
        $view->set('repository', $repository);
        $view->set('branch', $branch);
        $view->set('branches', $branches);
        $view->set('builder', $builder);

        $this->response->setView($view);
    }

    /**
     * Action to delete a builder
     * @param OrmManager $orm
     * @param string $repository
     * @param string $branch
     * @param string $builder
     * @return null
     */
    public function deleteAction(OrmManager $orm, $repository, $branch, $builder) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $builderModel = $orm->getDbudBuilderModel();
        $builder = $builderModel->getBuilderBySlug($builder);
        if (!$builder || $builder->repository != $repository->id || $builder->branch != $branch) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->request->isPost()) {
            if (!$this->request->getBodyParameter('cancel')) {
                $builderModel->delete($builder);
            }

            $this->response->setRedirect($this->getUrl('dbud.repository.integration', array('repository' => $repository->slug, 'branch' => $branch)));

            return;
        }

        $view = $this->createView('dbud/builder.delete', 'dbud.title.repository.detail', $repository);
        $view->set('branch', $branch);
        $view->set('builder', $builder);

        $this->response->setView($view);
    }

}