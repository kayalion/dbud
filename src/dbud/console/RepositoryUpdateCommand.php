<?php

namespace dbud\console;

use zibo\core\console\command\AbstractCommand;
use zibo\core\console\exception\ConsoleException;
use zibo\core\console\output\Output;
use zibo\core\console\InputValue;

/**
 * Command to queue a repository update
 */
class RepositoryUpdateCommand extends AbstractCommand {

    /**
     * Constructs a new command
     * @return null
     */
    public function __construct() {
        parent::__construct('repository update', 'Queues an update for all the repositories');
        $this->addArgument('repository', 'Id of the repository to update', false);
    }

    /**
     * Executes the command
     * @param zibo\core\console\InputValue $input The input
     * @param zibo\core\console\output\Output $output Output interface
     * @return null
     */
    public function execute(InputValue $input, Output $output) {
        $ormManager = $this->zibo->getDependency('zibo\\library\\orm\\OrmManager');

        $repositoryModel = $ormManager->getDbudRepositoryModel();
        $activityModel = $ormManager->getDbudActivityModel();

        $repository = $input->getArgument('repository');
        if ($repository) {
            $repository = $repositoryModel->getById($repository, 0);
            if (!$repository) {
                throw new ConsoleException('Repository not found');
            }

            $repositories = array($repository);
        } else {
            $query = $repositoryModel->createQuery();
            $query->setRecursiveDepth(0);

            $repositories = $query->query();
        }

        foreach ($repositories as $repository) {
            $output->write('Queue update for repository ' . $repository->id . '#' . $repository->name);

            $activityModel->queueRepositoryUpdate($repository);
        }
    }

}