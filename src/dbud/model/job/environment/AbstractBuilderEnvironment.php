<?php

namespace dbud\model\job\environment;

use dbud\model\data\BuilderData;

use zibo\library\log\Log;

use \Exception;

/**
 * Abstract implementation of a builder environment
 */
abstract class AbstractBuilderEnvironment implements BuilderEnvironment {

    /**
     * Instance of the log
     * @var zibo\library\log\Log
     */
    protected $log;

    /**
     * Instance of the builder
     * @var dbud\model\data\BuilderData
     */
    protected $builder;

    /**
     * Revision to build
     * @var string
     */
    protected $revision;

    /**
     * Instance of the exception of the last run
     * @var Exception
     */
    protected $exception;

    /**
     * Sets the log
     * @param zibo\library\log\Log
     * @return null
     */
    public function setLog(Log $log = null) {
        $this->log = $log;
    }

    /**
     * Sets the builder and revision
     * @param dbud\model\data\BuilderData $builder
     * @param string $revision
     * @return null
     */
    public function setBuilder(BuilderData $builder, $revision) {
        $this->builder = $builder;
        $this->revision = $revision;
    }

    /**
     * Sets the exception of the last run
     * @param Exception $exception
     * @return null
     */
    protected function setException(Exception $exception) {
        $this->exception = $exception;
    }

    /**
     * Gets the exception of the last run
     * @return Exception
     */
    public function getException() {
        return $this->exception;
    }

    /**
     * Gets the variables to parse in script commands
     * @param string $directory
     * @return array
     */
    protected function getCommandVariables($directory) {
        return array(
            'branch' => $this->builder->branch,
            'dir' => $directory,
            'repository' => $this->builder->repository->repository,
            'revision' => $this->revision,
        );
    }

    /**
     * Gets the commands to copy the repository
     * @return array
     */
    protected function getCopyRepositoryCommands() {
        return array(
            '# Cloning repository',
            'git clone --depth=50 --branch=%branch% %repository% .',
            'git checkout -qf %revision%',
        );
    }

    /**
     * Executes a command on the local system
     * @param string $command
     * @return string Output of the command
     * @throws Exception
     */
    protected function executeCommand($command) {
        $output = array();

        if ($this->log) {
            $this->log->logDebug('Executing ' . $command);
        }

        if (strpos($command, ' 2>') === false) {
            $command .= ' 2>&1';
        }

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception('Command returned code ' . $returnVar . ': ' . $command);
        }

        return $this->parseCommandOutput($output);
    }

    /**
     * Parses the output of a command to log output
     * @param array $output
     * @return string
     */
    protected function parseCommandOutput(array $output) {
        $log = '';

        foreach ($output as $line) {
            $log .= '# | ' . $line . "\n";
        }

        return $log;
    }

 }