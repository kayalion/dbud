<?php

namespace dbud;

use zibo\app\model\MenuItem;
use zibo\app\model\Taskbar;

use zibo\core\Zibo;

use zibo\library\filesystem\File;
use zibo\library\queue\model\data\QueueData;

/**
 * Module for the Deploy-Buddy
 */
class Module {

    /**
     * State of a new object
     * @var string
     */
    const STATE_NEW = 'new';

    /**
     * State of a ready object
     * @var string
     */
    const STATE_READY = 'ready';

    /**
     * State of a working object
     * @var string
     */
    const STATE_WORKING = "working";

    /**
     * State of a object with an error
     * @var string
     */
    const STATE_ERROR = "error";

    /**
     * State of a object with a warning
     * @var string
     */
    const STATE_WARNING = "warning";

    /**
     * State of a object which is ok
     * @var string
     */
    const STATE_OK = "ok";

    /**
     * Add the menu item to the taskbar
     * @param zibo\core\Zibo $zibo Instance of zibo
     * @param zibo\app\model\Taskbar $taskbar Instance of the taskbar
     * @return null
     */
    public function prepareTaskbar(Zibo $zibo, Taskbar $taskbar) {
        $applicationsMenu = $taskbar->getApplicationsMenu();

        $menuItem = new MenuItem();
        $menuItem->setTranslation('dbud.title.activity');
        $menuItem->setRoute('dbud.activity');
        $applicationsMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('dbud.title.repository.overview');
        $menuItem->setRoute('dbud.repository.overview');
        $applicationsMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('dbud.title.project.overview');
        $menuItem->setRoute('dbud.project.overview');
        $applicationsMenu->addMenuItem($menuItem);
    }

    /**
     * Gets the public key of the system
     * @param zibo\core\Zibo $zibo
     * @return boolean|string
     */
    public function getPublicKey(Zibo $zibo) {
        $publicKeyFile = $zibo->getParameter('dbud.ssh.key.public');
        if (!$publicKeyFile) {
            return false;
        }

        $publicKeyFile = new File($publicKeyFile);
        if (!$publicKeyFile->exists()) {
            return false;
        }

        return $publicKeyFile->read();
    }

    /**
     * Handle crashed jobs
     * @param zibo\core\Zibo $zibo
     * @param zibo\library\queue\model\data\QueueData $job
     * @return null
     */
    public function handleCrashedQueueJob(Zibo $zibo, QueueData $job) {
        $orm = $zibo->getDependency('zibo\\library\\orm\\OrmManager');

        $activityModel = $orm->getDbudActivityModel();

        $activity = $activityModel->getActivityForQueueJob($job->id);
        if (!$activity) {
            return;
        }

        if ($activity->state != self::STATE_ERROR) {
            $activity->state = self::STATE_ERROR;
            $activity->description .= "\n\n# Queue worker for this job has crashed.";

            $activityModel->save($activity, 'description');
            $activityModel->save($activity, 'state');
        }

        if (!$activity->dataType) {
            $model = $orm->getDbudRepositoryModel();
            $id = $activity->repository;
        } else {
            $model = $orm->getModel($activity->dataType);
            $id = $activity->dataId;
        }

        $object = $model->getById($id, 0);
        $object->state = self::STATE_ERROR;

        $model->save($object, 'state');
    }

}