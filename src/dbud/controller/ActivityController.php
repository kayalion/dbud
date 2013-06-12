<?php

namespace dbud\controller;

use zibo\library\orm\OrmManager;

/**
 * Activity controller
 */
class ActivityController extends AbstractController {

    /**
     * Action to show an overview of the activities
     * @param OrmManager $orm
     * @return null
     */
    public function overviewAction(OrmManager $orm) {
        $model = $orm->getDbudActivityModel();

        $repository = $this->request->getQueryParameter('repository');

        $rowsPerPage = 10;
        $page = $this->request->getQueryParameter('page', 1);
        if (!is_numeric($page) || $page < 0) {
            $url = $this->getUrl('dbud.activity') . '?page=1';
            if ($repository) {
                $url .= '&repository=' . $repository;
            }

            $this->response->setRedirect($url);

            return;
        }

        $numActivities = $model->countActivities($repository);
        $pages = ceil($numActivities / $rowsPerPage);
        $activities = $model->getActivities($repository, $rowsPerPage, ($page - 1) * $rowsPerPage);

        $url = $this->getUrl('dbud.activity') . '?page=%page%';
        if ($repository) {
            $url .= '&repository=' . $repository;
        }

        $view = $this->createView('dbud/activity', 'dbud.title.activity');
        $view->set('activities', $activities);
        $view->set('page', $page);
        $view->set('pages', $pages);
        $view->set('paginationUrl', $url);

        $this->response->setView($view);
    }

}