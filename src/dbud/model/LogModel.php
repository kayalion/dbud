<?php

namespace dbud\model;

use zibo\library\orm\model\behaviour\DatedBehaviour;
use zibo\library\orm\model\GenericModel;

/**
 * Log model
 */
class LogModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudLog';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new DatedBehaviour());
    }

    /**
     * Gets the logs of the project
     * @return array
     */
    public function getLogs($number = 10) {
        $query = $this->createQuery();
        $query->addOrderBy('{id} DESC');
        $query->setLimit($number, 0);

        return $query->query();
    }

    /**
     * Gets the logs of the project
     * @return array
     */
    public function getLogsForProject($projectId, $number = 10) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{project} = %1%', $projectId);
        $query->addOrderBy('{id} DESC');
        $query->setLimit($number, 0);

        return $query->query();
    }

    /**
     * Logs a message for a project
     * @param integer $project
     * @param string $message
     * @return null
     */
    public function logMessage($project, $message) {
        $log = $this->createData();
        $log->project = $project;
        $log->message = $message;

        $this->save($log);
    }

    /**
     * Delete project and floating logs
     * @param string $projectId
     * @return null
     */
    public function deleteProjectLogs($projectId) {
        $query = $this->createQuery();
        $query->addCondition('{project} = %1% OR {project} IS NULL', $projectId);

        $logs = $query->query();

        $this->delete($logs);
    }

}