<?php

namespace dbud\model;

use dbud\model\job\BuilderRunJob;
use dbud\model\job\RepositoryInitJob;
use dbud\model\job\RepositoryUpdateJob;
use dbud\model\job\ServerDeployJob;

use dbud\Module;

use zibo\library\orm\model\behaviour\DatedBehaviour;
use zibo\library\orm\model\GenericModel;
use zibo\library\queue\model\QueueModel;
use zibo\library\queue\model\QueueJob;

use \Exception;

/**
 * Activity model
 */
class ActivityModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudActivity';

    /**
     * Warning state
     * @var string
     */
    const TYPE_WARNING = 'warning';

    /**
     * Error state
     * @var string
     */
    const TYPE_ERROR = 'error';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new DatedBehaviour());
    }

    /**
     * Counts the activities
     * @return array
     */
    public function countActivities($repositoryId = null) {
        $query = $this->createQuery();

        if ($repositoryId) {
            $query->addCondition('{repository} = %1%', $repositoryId);
        }

        return $query->count();
    }

    /**
     * Gets the activities
     * @return array
     */
    public function getActivities($repositoryId = null, $number = 10, $offset = 0) {
        $query = $this->createQuery();

        if ($repositoryId) {
            $query->addCondition('{repository} = %1%', $repositoryId);
        }

        $query->addOrderBy('{id} DESC');

        $query->setLimit($number, $offset);

        return $query->query();
    }

    /**
     * Get the current activity of a queue job
     * @param integer $jobId
     * @return null|ActivityData
     */
    public function getActivityForQueueJob($jobId) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{job} = %1%', $jobId);
        $query->addOrderBy('{id} DESC');

        return $query->queryFirst();
    }

    /**
     * Gets the activities with a queue job
     * @param integer $repositoryId
     * @param string $status
     * @return array
     */
    public function getQueueForRepository($repositoryId, $status = null) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->removeFields('{repository}');
        $query->addCondition('{repository} = %1%', $repositoryId);
        $query->addCondition('{job} IS NOT NULL AND {job} <> 0');

        if ($status) {
            $query->addCondition('{job.status} = %1%', $status);
        }

        $query->addOrderBy('{id} DESC');

        return $query->query();
    }

    /**
     * Queues a repository initialization
     * @param RepositoryData $repository
     * @return null
     */
    public function queueRepositoryInit($repository) {
        $queue = $this->getQueueForRepository($repository->id, QueueModel::STATUS_WAITING);
        if ($queue) {
            $queueModel = $this->orm->getQueueModel();

            foreach ($queue as $activity) {
                $queueModel->delete($activity->job);
            }

            $this->logWarning($repository->id, 'Deleted waiting queue jobs');
        }

        $job = new RepositoryInitJob();
        $job->setRepository($repository);

        $jobId = $this->queue($job);

        $this->logActivity($repository->id, 'Queued initialization', null, null, $jobId);
    }

    /**
     * Queues a repository update
     * @param RepositoryData $repository
     * @return null
     */
    public function queueRepositoryUpdate($repository) {
        $query = $this->createQuery();
        $query->addCondition('{repository} = %1%', $repository->id);
        $query->addCondition('{dataType} IS NULL AND {dataId} IS NULL');
        $query->addCondition('{job.className} = %1%', 'dbud\\model\\job\\RepositoryUpdateJob');
        $query->addCondition('{job.status} = %1%', QueueModel::STATUS_WAITING);

        if ($query->count()) {
            return;
        }

        $job = new RepositoryUpdateJob();
        $job->setRepository($repository);

        $jobId = $this->queue($job);

        $this->logActivity($repository->id, 'Queued update', null, null, $jobId);
    }

    /**
     * Queues a build
     * @param BuilderData $builder
     * @param boolean $isManual
     * @return null
     */
    public function queueBuild($builder, $isManual = false) {
        $job = new BuilderRunJob();
        $job->setBuilder($builder);
        $job->setIsManual($isManual);

        $jobId = $this->queue($job);

        $this->logActivity($builder->repository->id, 'Queued build for ' . $builder->branch . ' with ' . $builder->name . "\n\n" . $builder->script, 'DbudBuilder', $builder->id, $jobId);
    }

    /**
     * Queues a deployment
     * @param ServerData $server
     * @return null
     */
    public function queueDeploy($server) {
        $job = new ServerDeployJob();
        $job->setServer($server);

        $jobId = $this->queue($job);

        $this->logActivity($server->repository->id, 'Queued deployment for ' . $server->branch . ' to ' . $server->name, 'DbudServer', $server->id, $jobId);
    }

    /**
     * Queues a job through the queue dispatcher
     * @param zibo\library\queue\QueueJob $queueJob
     * @return id Id of the job
     */
    protected function queue(QueueJob $queueJob) {
        $zibo = $this->orm->getZibo();
        $dispatcher = $zibo->getDependency('zibo\\library\\queue\\model\\dispatcher\\QueueDispatcher', 'dbud');

        return $dispatcher->queue($queueJob);
    }

    /**
     * Activities a message for a repository
     * @param integer $repository
     * @param string $description
     * @param string $dataType
     * @param string $dataId
     * @param string $job
     * @param string $state
     * @return null
     */
    public function logActivity($repository, $description, $dataType = null, $dataId = null, $job = null, $state = null) {
        if (!$state) {
            $state = Module::STATE_OK;
        }

        $activity = $this->createData();
        $activity->repository = $repository;
        $activity->dataType = $dataType;
        $activity->dataId = $dataId;
        $activity->description = trim($description);
        $activity->state = $state;
        $activity->job = $job;

        $this->save($activity);
    }

    /**
     * Activities a warning message for a repository
     * @param integer $repository
     * @param string $description
     * @param string $dataType
     * @param string $dataId
     * @param string $job
     * @return null
     */
    public function logWarning($repository, $description, $dataType = null, $dataId = null, $job = null) {
        $this->logActivity($repository, $description, $dataType, $dataId, $job, Module::STATE_WARNING);
    }

    /**
     * Activities a error message for a repository
     * @param integer $repository
     * @param string $message
     * @param Exception $exception
     * @return null
     */
    public function logError($repository, $description, Exception $exception = null, $dataType = null, $dataId = null, $job = null) {
        $description = trim($description);
        if ($exception) {
            $description .= "\n\n" . $this->getExceptionMessage($exception);
        }

        $this->logActivity($repository, $description, $dataType, $dataId, $job, Module::STATE_ERROR);
    }

    /**
     * Gets the exception as a string message
     * @param Exception $exception
     * @return string
     */
    protected function getExceptionMessage(Exception $exception) {
        $message = $exception->getMessage();
        if ($message) {
            $message = get_class($exception) . ': ' . $message;
        } else {
            $message = get_class($exception);
        }

        $message .= "\n\n" . $exception->getTraceAsString();

        $previous = $exception->getPrevious();
        if ($previous) {
            $message .= "\n\n\Caused by:\n\n" . $this->getExceptionMessage($previous);
        }

        return $message;
    }

    /**
     * Delete repository and floating activities
     * @param string $repositoryId
     * @return null
     */
    public function deleteRepositoryActivities($repositoryId) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{repository} = %1% OR {repository} IS NULL', $repositoryId);

        $activities = $query->query();

        $this->delete($activities);
    }

}