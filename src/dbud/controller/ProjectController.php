<?php

namespace dbud\controller;

use dbud\model\ProjectModel;

use zibo\app\view\FormView;

use zibo\jquery\Module as JQueryModule;

use zibo\library\http\Response;
use zibo\library\orm\OrmManager;
use zibo\library\validation\exception\ValidationException;

/**
 * Controller of the projects
 */
class ProjectController extends AbstractController {

    /**
     * Action to show an overview od the projects
     * @param OrmManager $orm
     * @return null
     */
    public function overviewAction(OrmManager $orm) {
        $projectModel = $orm->getDbudProjectModel();
        $projects = $projectModel->getProjects();

        $view = $this->createView('dbud/project.overview', 'dbud.title.project.overview');
        $view->set('projects', $projects);

        $this->response->setView($view);
    }

    /**
     * Action to show the flow of a project
     * @param OrmManager $orm
     * @param string $project
     * @return null
     */
    public function flowAction(OrmManager $orm, $project) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $availableBuilders = array();
        $availableServers = array();

        $builderModel = $orm->getDbudBuilderModel();
        $serverModel = $orm->getDbudServerModel();

        foreach ($project->repositories as $repository) {
            $builders = $builderModel->getBuildersForRepository($repository->id);
            foreach ($builders as $builder) {
                $builder->repository = $repository;

                $availableBuilders[$builder->id] = $builder;
            }

            $servers = $serverModel->getServersForRepository($repository->id);
            foreach ($servers as $server) {
                $server->repository = $repository;

                $availableServers[$server->id] = $server;
            }
        }

        $javascript = 'dbudProjectFlow.init("' . $this->getUrl('dbud.project.flow.submit', array('project' => $project->slug)) . '");' . "\n";
        foreach ($project->flow as $flowData) {
            $target = $flowData->getElementId();

            if ($flowData->dataType == 'DbudServer') {
                $flowData->data = $availableServers[$flowData->dataId];

                unset($availableServers[$flowData->dataId]);
            } else {
                $flowData->data = $availableBuilders[$flowData->dataId];

                unset($availableBuilders[$flowData->dataId]);
            }

            if ($flowData->previous) {
                $source = $project->flow[$flowData->previous]->getElementId();
            } else {
                $source = 'repository-0';
            }

            $javascript = '$("#' . $target . '").css("top", "' . $flowData->positionTop . '").css("left", "' . $flowData->positionLeft . '");' . "\n" . $javascript;
            $javascript .= 'jsPlumb.connect({ source: "' . $source . '", target: "' . $target . '"});' . "\n";
        }

        $view = $this->createView('dbud/project.flow', 'dbud.title.project.detail');
        $view->setPageSubTitle($project->name);
        $view->set('project', $project);
        $view->set('builders', $availableBuilders);
        $view->set('servers', $availableServers);
        $view->addJavascript(JQueryModule::SCRIPT_JQUERY_UI);
        $view->addJavascript('js/dbud/jquery.jsPlumb-1.4.1-all.js');
        $view->addJavascript('js/dbud/flow.js');
        $view->addInlineJavascript('jsPlumb.bind("ready", function() { ' . $javascript . ' });');

        $this->response->setView($view);
        $this->response->setView($view);
    }

    /**
     * Action to save the flow of a project
     * @param OrmManager $orm
     * @param string $project
     * @return null
     */
    public function flowSaveAction(OrmManager $orm, $project) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $data = $this->request->getBodyParameter('data', array());

        $flowModel = $orm->getDbudFlowModel();
        $flowModel->saveFlowForProject($project, $data);
    }

    /**
     * Action to add or edit a project
     * @param OrmManager $orm
     * @param string $project
     * @return null
     */
    public function formAction(OrmManager $orm, $project = null) {
        $projectModel = $orm->getDbudProjectModel();
        if ($project) {
            $project = $projectModel->getProjectBySlug($project);
            if (!$project) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $project = $projectModel->createData();
        }

        $repositoryModel = $orm->getDbudRepositoryModel();
        $repositories = $repositoryModel->getDataList();

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($project);
        $formBuilder->addRow('name', 'string', array(
            'label' => $translator->translate('dbud.label.project'),
            'description' => $translator->translate('dbud.label.project.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('repositories', 'select', array(
            'label' => $translator->translate('dbud.label.repositories'),
            'description' => $translator->translate('dbud.label.repositories.description'),
            'options' => $repositories,
            'multiselect' => true,
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                if ($project) {
                    $this->response->setRedirect($this->getUrl('dbud.project.detail', array('project' => $project)));
                } else {
                    $this->response->setRedirect($this->getUrl('dbud.project.overview'));
                }

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();

                $projectModel->save($data);

                $this->response->setRedirect($this->getUrl('dbud.project.detail', array('project' => $data->slug)));

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

                $form->setValidationException($exception);
            }
        }

        if ($project->id) {
            $formAction = $this->getUrl('dbud.project.edit.submit', array('project' => $project->slug));
        } else {
            $formAction = $this->getUrl('dbud.project.add.submit');
        }

        $view = new FormView($form->getFormView(), $formAction);

        if ($project->id) {
            $view->setPageTitle($translator->translate('dbud.title.project.edit'));
            $view->setPageSubTitle($project->name);
        } else {
            $view->setPageTitle($translator->translate('dbud.title.project.add'));
        }

        $this->response->setView($view);
    }

    /**
     * Action to delete a project
     * @param OrmManager $orm
     * @param string $project
     * @return null
     */
    public function deleteAction(OrmManager $orm, $project) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($project);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->request->isPost()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.project.detail', array('project' => $project->slug)));

                return;
            }

            $projectModel->delete($project);

            $this->response->setRedirect($this->getUrl('dbud.project.overview'));

            return;
        }

        $view = $this->createView('dbud/project.delete', 'dbud.title.project.detail', $project);

        $this->response->setView($view);
    }

}