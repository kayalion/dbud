<?php

namespace dbud\model\job;

use dbud\model\data\ProjectData;

use zibo\queue\model\AbstractZiboQueueJob;

/**
 * Queue job to clone a project
 */
class ProjectCloneJob extends AbstractZiboQueueJob {

    /**
     * Project to clone
     * @var dbud\model\data\ProjectData
     */
    protected $project;

    /**
     * Sets the project to the job
     * @param ProjectData $project
     */
    public function setProject(ProjectData $project) {
        $this->project = $project;
    }

    /**
     * Invokes the implementation of the job
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run() {
        $orm = $this->zibo->getDependency('zibo\\library\\orm\\OrmManager');

        $projectModel = $orm->getDbudProjectModel();
        $projectModel->cloneProject($this->project);
    }

}