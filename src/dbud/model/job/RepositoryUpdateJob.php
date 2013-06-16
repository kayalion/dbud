<?php

namespace dbud\model\job;

use dbud\model\data\RepositoryData;
use dbud\model\ServerModel;

use dbud\Module;

use zibo\library\filesystem\File;

use zibo\queue\model\AbstractZiboQueueJob;

use \Exception;

/**
 * Queue job to update a repository
 */
class RepositoryUpdateJob extends AbstractZiboQueueJob {

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

        $repository = $repositoryModel->getById($this->repository->id, 0);
        if (!$repository) {
            $activityModel->logError($this->repository->id, 'Could not update repository: repository is deleted', null, null, null, $this->getJobId());

            return;
        }

        if ($repository->state != Module::STATE_READY) {
            $activityModel->logWarning($this->repository->id, 'Rescheduling update: repository is not ready', null, null, $this->getJobId());

            return time();
        }

        $activityModel->logActivity($repository->id, 'Updating repository', null, null, $this->getJobId());

        $repository->state = Module::STATE_WORKING;
        $repositoryModel->save($repository, 'state');

        try {
            $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
            $directory = $this->zibo->getParameter('dbud.directory.data');

            $directoryHead = $repository->getBranchDirectory($directory);

            $repositoryHead = $git->createRepository($directoryHead);
            $repositoryHead->pullRepository();

            $branches = $repositoryHead->getBranches();
            foreach ($branches as $branch) {
                $directoryBranch = $this->repository->getBranchDirectory($directory, $branch);
                if (!$directoryBranch->exists()) {
                    $directoryHead->copy($directoryBranch);

                    $repositoryBranch = $git->createRepository($directoryBranch);
                    if ($repositoryBranch->getBranch() != $branch) {
                        $repositoryBranch->checkoutBranch($branch);
                    }
                } else {
                    $repositoryBranch = $git->createRepository($directoryBranch);
                    $repositoryBranch->pullRepository();
                }

                $revisions[$branch] = $repositoryBranch->getRevision();
            }

            $repository->state = Module::STATE_READY;

            $repositoryModel->save($repository, 'state');

            $flowModel = $orm->getDbudFlowModel();
            $flowModel->onRepositoryUpdate($repository, $revisions);

            $log = "\n\n";
            foreach ($revisions as $branch => $revision) {
                $log .= $branch . ': ' . $revision . "\n";
            }

            $activityModel->logActivity($repository->id, 'Updated repository' . $log, null, null, $this->getJobId());
        } catch (Exception $exception) {
            $this->repository->state = Module::STATE_ERROR;

            $repositoryModel->save($this->repository, 'state');

            $activityModel->logError($repository->id, 'Could not update the repository', $exception, null, null, $this->getJobId());

            throw $exception;
        }
    }

}