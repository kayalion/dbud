<?php

namespace dbud\model\job;

use dbud\model\data\BuilderData;
use dbud\model\ServerModel;

use dbud\Module;

use zibo\library\orm\OrmManager;
use zibo\library\Timer;

use zibo\queue\model\AbstractZiboQueueJob;

use \Exception;

/**
 * Queue job to run a builder script
 */
class BuilderRunJob extends AbstractZiboQueueJob {

    /**
     * Builder to run
     * @var dbud\model\data\BuilderData
     */
    protected $builder;

    /**
     * Flag to see if this is a manual build
     * @var boolean
     */
    protected $isManual;

    /**
     * Sets the builder to the job
     * @param BuilderData $builder
     */
    public function setBuilder(BuilderData $builder) {
        $this->builder = $builder;
    }

    /**
     * Sets the manual run flag
     * @param boolean $isManual
     * @return null
     */
    public function setIsManual($isManual) {
        $this->isManual = $isManual;
    }

    /**
     * Invokes the implementation of the job
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run() {
        $orm = $this->zibo->getDependency('zibo\\library\\orm\\OrmManager');

        $activityModel = $orm->getDbudActivityModel();
        $builderModel = $orm->getDbudBuilderModel();
        $repositoryModel = $orm->getDbudRepositoryModel();
        $queueModel = $orm->getQueueModel();

        $repository = $repositoryModel->getById($this->builder->repository->id);
        if ($repository->state != Module::STATE_READY) {
            throw new Exception('Repository is not ready');
        }

        $activityModel->logActivity($repository->id, 'Running builder ' . $this->builder->name . ' for ' . $this->builder->branch, 'DbudBuilder', $this->builder->id, $this->getJobId());

        $timer = new Timer();

        $this->builder->state = Module::STATE_WORKING;
        $builderModel->save($this->builder, 'state');

        $git = $repositoryModel->getGitRepository($repository, $this->builder->branch);
        $revision = $git->getRevision();

        $log = "# Commit: " . $revision . "\n";

        try {
            $environment = $this->zibo->getParameter('dbud.builder.environment', 'local');
            $environment = $this->zibo->getDependency('dbud\\model\\job\\environment\\BuilderEnvironment', $environment);
            $environment->setLog($this->zibo->getLog());
            $environment->setBuilder($this->builder, $revision);
            $log .= $environment->runBuilder();

            $exception = $environment->getException();
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->builder->dateBuilt = time();
        $builderModel->save($this->builder, 'dateBuilt');

        if ($exception) {
            $this->builder->state = Module::STATE_ERROR;
            $builderModel->save($this->builder, 'state');

            $log .= "# Builder took " . $timer->getTime() . " seconds.";

            $activityModel->logError($repository->id, 'Builder ' . $this->builder->name . ' for ' . $this->builder->branch . " ran into an error\n\n" . $log . "\n\n" . $exception->getMessage(), $exception, 'DbudBuilder', $this->builder->id, $this->getJobId());

            return;
        }

        $this->builder->revision = $revision;
        $builderModel->save($this->builder, 'revision');

        $log .= $this->queueDeployment($orm, $revision);

        $this->builder->state = Module::STATE_OK;
        $builderModel->save($this->builder, 'state');

        $log .= "# Builder took " . $timer->getTime() . " seconds.";

        $activityModel->logActivity($repository->id, 'Finished running builder ' . $this->builder->name . ' for ' . $this->builder->branch . "\n\n" . $log, 'DbudBuilder', $this->builder->id, $this->getJobId());
    }

    /**
     * Queues the deployment of the repository
     * @param OrmManager $orm
     * @param string $revision
     * @return string Log message
     */
    protected function queueDeployment(OrmManager $orm, $revision) {
        $log = '';

        if ($this->isManual) {
            return $log;
        }

        $orm->getQueueModel()->updateStatus($this->getJobId(), 'Queueing deployment');

        $deployAllowed = true;

        $builders = $orm->getDbudBuilderModel()->getBuildersForRepository($this->builder->repository->id, $this->builder->branch);
        foreach ($builders as $builder) {
            if ($builder->id == $this->builder->id || ($revision && $builder->revision == $revision && $builder->state == Module::STATE_OK)) {
                continue;
            }

            $deployAllowed = false;

            break;
        }

        if ($deployAllowed) {
            $activityModel = $orm->getDbudActivityModel();
            $serverModel = $orm->getDbudServerModel();

            $servers = $serverModel->getServersForRepository($this->builder->repository->id, $this->builder->branch);
            foreach ($servers as $server) {
                if ($server->revision == $revision || $server->mode != ServerModel::MODE_AUTOMATIC) {
                    continue;
                }

                $server->repository = $this->builder->repository;

                $activityModel->queueDeploy($server);

                $log .= "# Queued deployment from " . $this->builder->branch . " to " . $server->getDsn() . "\n";
            }
        }

        return $log;
    }

}