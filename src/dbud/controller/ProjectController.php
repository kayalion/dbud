<?php

namespace dbud\controller;

use dbud\model\ProjectModel;

use zibo\app\controller\AbstractController;
use zibo\app\view\BaseView;
use zibo\app\view\FormView;

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
        $translator = $this->getTranslator();

        $projectModel = $orm->getDbudProjectModel();
        $projects = $projectModel->getProjects();

        $view = new BaseView('dbud/project.overview');
        $view->setPageTitle($translator->translate('dbud.title.project.overview'));
        $view->set('projects', $projects);

        $this->response->setView($view);
    }

    /**
     * Action to show the detail of a project
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function detailAction(OrmManager $orm, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($slug);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $environmentModel = $orm->getDbudEnvironmentModel();
        $environments = $environmentModel->getEnvironmentsForProject($project->id);

        $logModel = $orm->getDbudLogModel();
        $logs = $logModel->getLogsForProject($project->id);

        $translator = $this->getTranslator();

        $view = new BaseView('dbud/project.detail');
        $view->setPageTitle($translator->translate('dbud.title.project.detail'));
        $view->setPageSubTitle($project->name);
        $view->set('project', $project);
        $view->set('environments', $environments);
        $view->set('logs', $logs);

        if ($project->state == ProjectModel::STATE_TO_CLONE) {
            $view->addInlineJavascript('initializeState("' . $this->request->getUrl() . '", 5000);');
        }

        $this->response->setView($view);
    }

    /**
     * Action to add or edit a project
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function formAction(OrmManager $orm, $slug = null) {
        $projectModel = $orm->getDbudProjectModel();
        if ($slug) {
            $project = $projectModel->getProjectBySlug($slug);
            if (!$project) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $project = $projectModel->createData();
        }

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
        $formBuilder->addRow('repository', 'string', array(
            'label' => $translator->translate('dbud.label.repository'),
            'description' => $translator->translate('dbud.label.repository.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                if ($slug) {
                    $this->response->setRedirect($this->getUrl('dbud.project.detail', array('slug' => $slug)));
                } else {
                    $this->response->setRedirect($this->getUrl('dbud.project.overview'));
                }

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();
                $data->state = ProjectModel::STATE_TO_CLONE;

                $projectModel->save($data);

                $orm->getDbudQueueModel()->queueClone($data);

                $this->response->setRedirect($this->getUrl('dbud.project.detail', array('slug' => $data->slug)));

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

                $form->setValidationException($exception);
            }
        }

        if ($slug) {
            $formAction = $this->getUrl('dbud.project.edit.submit', array('slug' => $slug));
        } else {
            $formAction = $this->getUrl('dbud.project.add.submit');
        }

        $view = new FormView($form->getFormView(), $formAction);

        if ($slug) {
            $view->setPageTitle($translator->translate('dbud.title.project.edit'));
            $view->setPageSubTitle($project->name);
        } else {
            $view->setPageTitle($translator->translate('dbud.title.project.add'));
        }

        $this->response->setView($view);
    }

    /**
     * Action to show the queue of a project
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function queueAction(OrmManager $orm, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($slug);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $queue = $orm->getDbudQueueModel()->getQueueForProject($project->id);

        $translator = $this->getTranslator();

        $view = new BaseView('dbud/project.queue');
        $view->setPageTitle($translator->translate('dbud.title.project.queue'));
        $view->setPageSubTitle($project->name);
        $view->set('project', $project);
        $view->set('queue', $queue);
        $view->addJavascript('js/dbud/queue.js');
        $view->addInlineJavascript('initializeQueue("' . $this->request->getUrl() . '", 5000);');

        $this->response->setView($view);
    }

    /**
     * Action to delete a project
     * @param OrmManager $orm
     * @param string $slug
     * @return null
     */
    public function deleteAction(OrmManager $orm, $slug) {
        $projectModel = $orm->getDbudProjectModel();
        $project = $projectModel->getProjectBySlug($slug);
        if (!$project) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $projectModel->delete($project);

        $this->response->setRedirect($this->getUrl('dbud.project.overview'));
    }

}