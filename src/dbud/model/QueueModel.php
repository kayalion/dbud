<?php

namespace dbud\model;

use dbud\model\job\ProjectCloneJob;
use dbud\model\job\ServerDeployJob;

use zibo\library\orm\model\behaviour\DatedBehaviour;
use zibo\library\orm\model\GenericModel;
use zibo\library\queue\model\QueueJob;

/**
 * Queue model
 */
class QueueModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudQueue';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new DatedBehaviour());
    }

    /**
     * Queues a job
     * @param zibo\library\queue\QueueJob $queueJob
     * @return id Id of the job
     */
    protected function queue(QueueJob $queueJob) {
        $zibo = $this->orm->getZibo();
        $dispatcher = $zibo->getDependency('zibo\\library\\queue\\model\\dispatcher\\QueueDispatcher', 'dbud');

        return $dispatcher->queue($queueJob);
    }

    /**
     * Queues a clone
     * @param ProjectData $project
     * @return null
     */
    public function queueClone($project) {
        $job = new ProjectCloneJob();
        $job->setProject($project);

        $jobId = $this->queue($job);

        $queue = $this->createData();
        $queue->task = 'Clone ' . $project->repository;
        $queue->job = $jobId;
        $queue->dataType = "DbudProject";
        $queue->dataId = $project->id;

        $this->save($queue);
    }

    /**
     * Queues a deployment
     * @param ProjectData $project
     * @param EnvironmentData $environment
     * @param ServerData $server
     * @return null
     */
    public function queueDeploy($project, $environment, $server) {
        $job = new ServerDeployJob();
        $job->setProject($project);
        $job->setEnvironment($environment);
        $job->setServer($server);

        $jobId = $this->queue($job);

        $queue = $this->createData();
        $queue->task = 'Deploy ' . $environment->branch . ' to ' . $server->getDsn();
        $queue->job = $jobId;
        $queue->dataType = "DbudEnvironment";
        $queue->dataId = $environment->id;

        $this->save($queue);
    }

    /**
     * Gets the queue status for the provided environment
     * @param string $environmentId Id of the environment
     * @return array
     */
    public function getQueueForEnvironment($environmentId) {
        $queueModel = $this->orm->getQueueModel();

        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{dataType} = %1% AND {dataId} = %2%', 'DbudEnvironment', $environmentId);
        $query->addOrderBy('{id} DESC');

        $queue = $query->query();
        foreach ($queue as $id => $queueJob) {
            $queueJob->job = $queueModel->getQueueJob($queueJob->job);

            if (!$queueJob->job) {
                $this->delete($queueJob);

                unset($queue[$id]);

                continue;
            }
        }

        return $queue;
    }

    /**
     * Gets the queue status for the provided project
     * @param string $projectId Id of the project
     * @return array
     */
    public function getQueueForProject($projectId) {
        $environmentModel = $this->orm->getDbudEnvironmentModel();
        $environments = $environmentModel->getEnvironmentsForProject($projectId);

        $queueModel = $this->orm->getQueueModel();

        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->setOperator('OR');
        $query->addCondition('{dataType} = %1% AND {dataId} = %2%', 'DbudProject', $projectId);
        foreach ($environments as $environment) {
            $query->addCondition('{dataType} = %1% AND {dataId} = %2%', 'DbudEnvironment', $environment->id);
        }
        $query->addOrderBy('{id} DESC');

        $queue = $query->query();
        foreach ($queue as $id => $queueJob) {
            $queueJob->job = $queueModel->getQueueJob($queueJob->job);

            if (!$queueJob->job) {
                $this->delete($queueJob);

                unset($queue[$id]);

                continue;
            }
        }

        return $queue;
    }

}