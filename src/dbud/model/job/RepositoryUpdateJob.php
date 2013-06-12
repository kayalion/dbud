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
        $builderModel = $orm->getDbudBuilderModel();
        $repositoryModel = $orm->getDbudRepositoryModel();
        $serverModel = $orm->getDbudServerModel();

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

                $revision = $repositoryBranch->getRevision();

                $builders = $builderModel->getBuildersForRepository($repository->id, $branch);
                if ($builders) {
                    foreach ($builders as $builder) {
                        if ($builder->revision == $revision && $builder->state == Module::STATE_OK) {
                            continue;
                        }

                        $builder->repository = $repository;

                        $activityModel->queueBuild($builder);
                    }
                } else {
                    $servers = $serverModel->getServersForRepository($repository->id, $branch);
                    foreach ($servers as $server) {
                        if ($server->revision == $revision || $server->mode != ServerModel::MODE_AUTOMATIC) {
                            continue;
                        }

                        $server->repository = $repository;

                        $activityModel->queueDeploy($server);
                    }
                }
            }

            $descriptionFile = new File($directoryHead, '.git/description');
            $repository->description = $descriptionFile->read();
            $repository->state = Module::STATE_READY;

            $repositoryModel->save($repository, 'description');
            $repositoryModel->save($repository, 'state');

            $activityModel->logActivity($repository->id, 'Updated repository', null, null, $this->getJobId());
        } catch (Exception $exception) {
            $this->repository->state = Module::STATE_ERROR;

            $repositoryModel->save($this->repository, 'state');

            $activityModel->logError($repository->id, 'Could not update the repository', $exception, null, null, $this->getJobId());

            throw $exception;
        }
    }

}