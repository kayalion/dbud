<?php

namespace dbud\model\job;

use dbud\model\data\RepositoryData;

use dbud\Module;

use zibo\library\filesystem\File;
use zibo\library\queue\model\QueueModel;

use zibo\queue\model\AbstractZiboQueueJob;

use \Exception;

/**
 * Queue job to clone a repository
 */
class RepositoryInitJob extends AbstractZiboQueueJob {

    /**
     * Repository to initialize
     * @var dbud\model\data\RepositoryData
     */
    protected $repository;

    /**
     * Sets the repository to the job
     * @param RepositoryData $repository
     */
    public function setRepository(RepositoryData $repository) {
        $this->repository = $repository;
    }

    /**
     * Invokes the implementation of the job
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run() {
        $orm = $this->zibo->getDependency('zibo\\library\\orm\\OrmManager');

        $activityModel = $orm->getDbudActivityModel();
        $repositoryModel = $orm->getDbudRepositoryModel();

        $queue = $activityModel->getQueueForRepository($this->repository->id, QueueModel::STATUS_PROGRESS);
        if ($queue) {
            foreach ($queue as $activityId => $activity) {
                if ($activity->job == $this->getJobId()) {
                    unset($queue[$activityId]);
                }
            }

            if ($queue) {
                $activityModel->logWarning($this->repository->id, 'Requeued initializion: a job is still in progress', null, null, $this->getJobId());

                return time();
            }
        }

        $repository = $repositoryModel->getById($this->repository->id, 0);
        if (!$repository) {
            $activityModel->logError($this->repository->id, 'Could not initialize repository: repository is deleted', null, null, null, $this->getJobId());

            return;
        }

        $activityModel->logActivity($repository->id, 'Initializing repository ' . $repository->repository, null, null, $this->getJobId());

        try {
            $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
            $directory = $this->zibo->getParameter('dbud.directory.data');

            $directoryHead = $this->repository->getBranchDirectory($directory);
            if ($directoryHead->exists()) {
                $directoryHead->delete();
            }
            $directoryHead->create();

            $repositoryHead = $git->createRepository($directoryHead);
            $repositoryHead->cloneRepository($repository->repository);

            $branches = $repositoryHead->getBranches();
            foreach ($branches as $branch) {
                $directoryBranch = $this->repository->getBranchDirectory($directory, $branch);
                if ($directoryBranch->exists()) {
                    $directoryBranch->delete();
                }

                $directoryHead->copy($directoryBranch);

                $repositoryBranch = $git->createRepository($directoryBranch);
                if ($repositoryBranch->getBranch() != $branch) {
                    $repositoryBranch->checkoutBranch($branch);
                }
            }

            $descriptionFile = new File($directoryHead, '.git/description');
            $repository->description = $descriptionFile->read();
            $repository->state = Module::STATE_READY;

            $repositoryModel->save($repository, 'description');
            $repositoryModel->save($repository, 'state');

            $activityModel->logActivity($repository->id, 'Initialized repository ' . $repository->repository, null, null, $this->getJobId());
        } catch (Exception $exception) {
            $repository->state = Module::STATE_ERROR;

            $repositoryModel->save($repository, 'state');

            $activityModel->logError($this->repository->id, 'Could not initialize repository', $exception, null, null, $this->getJobId());

            throw $exception;
        }
    }

}