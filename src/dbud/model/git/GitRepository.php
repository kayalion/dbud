<?php

namespace dbud\model\git;

use zibo\library\filesystem\File;
use zibo\library\log\Log;

use \Exception;

class GitRepository {

    /**
     * Source for the log messages
     * @var string
     */
    const LOG_SOURCE = 'git';

    /**
     * Instance of the log
     * @var zibo\library\log\Log
     */
    protected $log;

    /**
     * Path to the binary of Git
     * @var string
     */
    protected $git;

    /**
     * Path of the remote repository
     * @var string
     */
    protected $remotePath;

    /**
     * Path of the local repository
     * @var string
     */
    protected $localPath;

    /**
     * Constructs a new Repository
     * @param string $remotePath
     * @param zibo\library\filesystem\File $localPath;
     * @return null
     */
    public function __construct($remotePath, File $localPath) {
        $this->remotePath = $remotePath;
        $this->localPath = $localPath;
        $this->git = 'git';
        $this->log = null;
    }

    /**
     * Sets the log
     * @param zibo\library\log\LOg $log
     * @return null
     */
    public function setLog(Log $log = null) {
        $this->log = $log;
    }

    /**
     * Sets the path to the Git binary
     * @param string $binary
     * @return null
     */
    public function setGitBinary($binary) {
        $this->git = $binary;
    }

    /**
     * Gets the local path of this repository
     * @return zibo\library\filesystem\File
     */
    public function getLocalPath() {
        return $this->localPath;
    }

    /**
     * Clones the repository to a local directory
     * @return boolean False when the repository is already cloned, true if a
     * clone has been performed
     * @throws Exception when the repository could not be cloned
     */
    public function cloneRepository() {
        if ($this->localPath->exists()) {
            $cwd = getcwd();
            chdir($this->localPath);

            $command = $this->git . ' remote -v';

            $output = array();
            exec($command, $output);
            $output = implode("\n", $output);

            chdir($cwd);

            if ($this->log) {
                $this->log->logDebug($command, $output, self::LOG_SOURCE);
            }

            if (strpos($output, $this->remotePath) !== false) {
                return false;
            }

            $this->localPath->delete();
        }

        $this->localPath->create();

        $command = $this->git . ' clone ' . $this->remotePath . ' ' . $this->localPath->getAbsolutePath() . ' 2>&1';

        $output = array();
        exec($command, $output);
        $output = implode("\n", $output);

        if ($this->log) {
            $this->log->logDebug($command, $output, self::LOG_SOURCE);
        }

        if (strpos($output, 'fatal:') !== false) {
            throw new Exception('Could not clone ' . $this->remotePath . ': ' . $output);
        }

        return true;
    }

    /**
     * Pulls the repository from origin
     * @return null
     * @throws Exception when the pull could not be completed
     */
    public function pullRepository() {
        $cwd = getcwd();
        chdir($this->localPath);

        $command = $this->git . ' pull';

        $output = array();
        exec($command, $output);
        $output = implode("\n", $output);

        chdir($cwd);

        if ($this->log) {
            $this->log->logDebug($command, $output, self::LOG_SOURCE);
        }

        if (strpos($output, 'fatal:') !== false) {
            throw new Exception('Could not pull ' . $this->localPath . ': ' . $output);
        }
    }

    /**
     * Checkout a branch
     * @param string $branch
     * @return null
     * @throws Exception when the checkout could not be completed
     */
    public function checkoutBranch($branch) {
        $cwd = getcwd();
        chdir($this->localPath);

        $command = $this->git . ' checkout ' . $branch . ' 2>&1';

        $output = array();
        exec($command, $output);
        $output = implode("\n", $output);

        chdir($cwd);

        if ($this->log) {
            $this->log->logDebug($command, $output, self::LOG_SOURCE);
        }

        if (strpos($output, 'Switched to') === false) {
            throw new Exception('Could not checkout ' . $branch . ' on ' . $this->localPath . ': ' . $output);
        }
    }

    /**
     * Gets the commit messages
     * @param string $since Id of a commit
     * @return array Array with GitCommitLog instances
     * @see dbud\model\git\GitCommitLog
     */
    public function getCommitLogs($since = null) {
        $cwd = getcwd();
        chdir($this->localPath);

        $command = $this->git . ' --no-pager log --summary 2>&1';
        if ($since) {
            $command .= ' ' . $since . '..';
        }

        $output = array();
        exec($command, $output);
        $output = implode("\n", $output);

        chdir($cwd);

        if ($this->log) {
            $this->log->logDebug($command, $output, self::LOG_SOURCE);
        }

        $commits = array();
        $commit = new GitCommitLog();

        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (strpos($line, 'commit ') === 0) {
                if ($commit->id) {
                    $commit->message = trim($commit->message);
                    $commits[$commit->id] = $commit;

                    $commit = new GitCommitLog();
                }

                $commit->id = substr($line, 7);

                continue;
            }

            if (strpos($line, 'Author: ') === 0) {
                $commit->author = substr($line, 8);

                continue;
            }

            if (strpos($line, 'Date: ') === 0) {
                $commit->author = substr($line, 6);

                continue;
            }

            if (strpos($line, 'create mode ') !== false || strpos($line, 'delete mode ') !== false) {
                $line = trim($line);
                list($action, $null, $mode, $path) = explode(' ', $line, 4);

                $file = new GitCommitFile();
                $file->action = $action;
                $file->mode = (integer) substr($mode, 2);
                $file->path = $path;

                $commit->files[$path] = $file;

                continue;
            }

            $commit->message .= $line . "\n";
        }

        if ($commit->id) {
            $commit->message = trim($commit->message);
            $commits[$commit->id] = $commit;
        }

        return $commits;
    }

    /**
     * Gets the branche names of this repository
     * @return array
     * @throws Exception when not a valid git repository
     */
    public function getBranches() {
        $cwd = getcwd();
        chdir($this->localPath);

        $command = $this->git . ' branch --list -a';

        $output = array();
        exec($command, $output);
        $output = implode("\n", $output);

        chdir($cwd);

        if ($this->log) {
            $this->log->logDebug($command, $output, self::LOG_SOURCE);
        }

        if (strpos($output, 'fatal:') !== false) {
            throw new Exception('Could not get the branches of ' . $this->localPath . ': ' . $output);
        }

        $branches = array();
        $output = explode("\n", $output);
        foreach ($output as $line) {
            $branch = trim(str_replace('*', '', $line));

            if (strpos($branch, 'remotes/origin/') === false) {
                continue;
            }

            $branch = str_replace('remotes/origin/', '', $branch);
            if (strpos($branch, '->') !== false) {
                continue;
            }

            $branches[$branch] = $branch;
        }

        return $branches;
    }

}