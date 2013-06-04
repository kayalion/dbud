<?php

namespace dbud\model\job;

use dbud\model\data\ProjectData;
use dbud\model\data\EnvironmentData;
use dbud\model\data\ServerData;

use zibo\library\filesystem\File;

use zibo\queue\model\AbstractZiboQueueJob;

use \Exception;

/**
 * Queue job to deploy a project to a server
 */
class ServerDeployJob extends AbstractZiboQueueJob {

    /**
     * Project to deploy
     * @var dbud\model\data\ProjectData
     */
    protected $project;

    /**
     * Environment to deploy
     * @var dbud\model\data\EnvironmentData
     */
    protected $environment;

    /**
     * Server to deploy to
     * @var dbud\model\data\ServerData
     */
    protected $server;

    /**
     * Sets the project to the job
     * @param ProjectData $project
     */
    public function setProject(ProjectData $project) {
        $this->project = $project;
    }

    /**
     * Sets the environment to the job
     * @param EnvironmentData $environment
     */
    public function setEnvironment(EnvironmentData $environment) {
        $this->environment= $environment;
    }

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
        $projectModel = $orm->getDbudProjectModel();

        $repositoryPath = $projectModel->getRepositoryPath($this->project, $this->environment->branch);
        if ($repositoryPath->exists()) {
            $repositoryPath->delete();
        }

        // pull branch
        $projectModel->pullBranch($this->project, $this->environment->branch);

        // check revision / files to copy and remove
        try {
            $files = array();
            $revision = null;

            $logs = $projectModel->getCommitLogs($this->project, $this->environment->branch, $this->server->revision);
            foreach ($logs as $log) {
                if (!$revision) {
                    $revision = $log->id;
                }

                foreach ($log->files as $file) {
                    if (isset($files[$file->path]) || strpos('/' . $file->path, $this->server->repositoryPath) !== 0) {
                        continue;
                    }

                    $files[$file->path] = $file;
                }
            }

            if (!$revision) {
                $revision = $this->server->revision;
            }

            $message = 'Deployed ' . $revision . ' from ' . $this->environment->branch . ' to ' . $this->server->getDsn() . "\n\n";

            // apply exclude filters
            $exclude = $this->server->parseExclude();
            if ($exclude) {
                foreach ($files as $path => $file) {
                    $regex = $this->isPathExcluded($path, $exclude);
                    if ($regex == false) {
                        continue;
                    }

                    unset($files[$path]);

                    $message .= 'skipped ' . $path . ' due to exclude rule ' . $regex . "\n";
                }
            }

            // perform actions
            $this->zibo->getLog()->logDebug('Deploying ' . $revision . ' from Environment#' . $this->environment->id . ' to ' . $this->server->getDsn());

            $protocol = $this->zibo->getDependency('dbud\\model\\protocol\\Protocol', $this->server->protocol);
            $log = $protocol->deploy($this->server, $repositoryPath, $files);

            // log deploy actions
            if ($log) {
                foreach ($log as $file => $status) {
                    $action = substr($file, 0, 1);
                    switch ($action) {
                        case '-':
                            if ($status) {
                                $message .= 'deleted file ' . substr($file, 1) . "\n";
                            } else {
                                $message .= 'could not delete file ' . substr($file, 1) . "\n";
                            }

                            break;
                        case '+':
                            if ($status) {
                                $message .= 'copied file ' . substr($file, 1) . "\n";
                            } else {
                                $message .= 'could not copy file ' . substr($file, 1) . "\n";
                            }

                            break;
                        case '@':
                            $message .= 'executed ' . substr($file, 1) . "\n" . $status  . "\n";

                            break;
                        case '#':
                            if (!$status) {
                                list($path, $mode) = explode(':', $file, 2);

                                $message .= 'could not chmod file ' . $path . ' to ' . $mode . "\n";
                            }

                            break;
                    }
                }
            } else {
                $message .= "already up-to-date";
            }

            $logModel = $orm->getDbudLogModel();
            $logModel->logMessage($this->project, $message);

            // update server
            if ($revision) {
                $this->server->revision = $revision;

                $serverModel = $orm->getDbudServerModel();
                $serverModel->save($this->server, 'revision');
            }

            // clean up
            $repositoryPath->delete();
        } catch (Exception $e) {
            $repositoryPath->delete();

            throw $e;
        }
    }

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