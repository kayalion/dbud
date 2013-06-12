<?php

namespace dbud\model\job;

use dbud\model\data\ServerData;
use dbud\model\RepositoryModel;

use dbud\Module;

use zibo\library\filesystem\File;
use zibo\library\Timer;

use zibo\queue\model\AbstractZiboQueueJob;

use \Exception;

/**
 * Queue job to deploy a project to a server
 */
class ServerDeployJob extends AbstractZiboQueueJob {

    /**
     * Server to deploy to
     * @var dbud\model\data\ServerData
     */
    protected $server;

    /**
     * Sets the server to the job
     * @param ServerData $server
     */
    public function setServer(ServerData $server) {
        $this->server = $server;
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
        $serverModel = $orm->getDbudServerModel();

        $repository = $repositoryModel->getById($this->server->repository->id, 0);
        if ($repository->state != Module::STATE_READY) {
            throw new Exception('Repository is not ready');
        }

        $activityModel->logActivity($this->server->repository->id, 'Deploying ' . $this->server->branch . ' to ' . $this->server->name, 'DbudServer', $this->server->id, $this->getJobId());

        $timer = new Timer();

        $this->server->state = Module::STATE_WORKING;
        $serverModel->save($this->server, 'state');

        $files = array();

        $log = 'Deployed ' . $this->server->branch . ' to ' . $this->server->name . "\n\n";

        // check revision
        $git = $repositoryModel->getGitRepository($repository, $this->server->branch);
        $revision = $git->getRevision();

        if ($revision) {
            $log .= "# Commit: " . $revision . "\n# Server: " . $this->server->getDsn() . "\n";
        } else {
            $log .= "# No commits in the repository\n";
        }

        // get changed files
        if ($revision && $this->server->revision != $revision) {
            if ($this->server->revision) {
                $output = $git->git('diff --name-status ' . $this->server->revision);
                foreach ($output as $file) {
                    list($action, $path) = explode("\t", $file, 2);

                    if (isset($files[$file]) || strpos('/' . $path, $this->server->repositoryPath) !== 0) {
                        continue;
                    }

                    $files[$path] = $action;
                }
            } else {
                $output = $git->getTree($this->server->branch, null, true);
                foreach ($output as $path => $null) {
                    if (strpos('/' . $path, $this->server->repositoryPath) !== 0) {
                        continue;
                    }

                    $files[$path] = 'A';
                }
            }
        }

        // apply exclude filters
        $exclude = $this->server->parseExclude();
        if ($exclude) {
            foreach ($files as $path => $action) {
                $regex = $this->isPathExcluded($path, $exclude);
                if ($regex === false) {
                    continue;
                }

                unset($files[$path]);

                $log .= '# [s] ' . $path . ' (' . $regex . ")\n";
            }
        }

        // get the protocol
        $protocol = $this->zibo->getDependency('dbud\\model\\protocol\\Protocol', $this->server->protocol);

        // perform actions
        $this->zibo->getLog()->logDebug('Deploying ' . substr($revision, 0, 7) . ' from ' . $this->server->branch . ' in '. $this->server->repository->repository . ' to ' . $this->server->getDsn());

        try {
            $output = $protocol->deploy($this->server, $git->getClient()->getWorkingDirectory(), $files);

            $isError = false;
        } catch (DeployException $exception) {
            $this->zibo->getLog()->logException($exception);

            $output = $exception->getLog();

            $isError = true;
        }

        // log deploy actions
        if ($output) {
            foreach ($output as $command => $commandOutput) {
                $log .= $command . "\n";

                if ($commandOutput === true || !$commandOutput) {
                    continue;
                }

                if (!is_array($commandOutput)) {
                    $commandOutput = array($commandOutput);
                }

                foreach ($commandOutput as $line) {
                    $log .= "| " . $line;
                }
            }
        }

        // update server
        $this->server->dateDeployed = time();
        $serverModel->save($this->server, 'dateDeployed');

        $log .= "# Deployment took " . $timer->getTime() . " seconds.";

        if ($isError) {
            $this->server->state = Module::STATE_ERROR;
            $serverModel->save($this->server, 'state');

            $activityModelModel->logError($this->server->repository->id, $log, null, 'DbudServer', $this->server->id, $this->getJobId());
        } else {
            $this->server->revision = $revision;
            $serverModel->save($this->server, 'revision');

            $this->server->state = Module::STATE_OK;
            $serverModel->save($this->server, 'state');

            $activityModel->logActivity($this->server->repository->id, $log, 'DbudServer', $this->server->id, $this->getJobId());
        }
    }

    /**
     * Checks if the provided path matches a exclude regex
     * @param string $path
     * @param array $exceludes
     * @return boolean|string False when the path does not match, the regex if
     * it matches
     */
    protected function isPathExcluded($path, array $excludes) {
        $pathFile = new File($path);

        foreach ($excludes as $exclude) {
            if (strpos($exclude, '**/') === 0) {
                $regex = substr($exclude, 3);
                $file = $pathFile->getName();
            } else {
                $regex = $exclude;
                $file = $path;
            }

            $regex = '/' . str_replace('/', '\\/', str_replace('*', '(.*)', $regex)) . '/';
            if (preg_match($regex, $file)) {
                return $exclude;
            }
        }

        return false;
    }

}