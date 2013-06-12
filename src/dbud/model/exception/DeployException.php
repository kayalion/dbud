<?php

namespace dbud\model\exception;

use \Exception;

/**
 * Exception thrown by a deploy protocol
 */
class DeployException extends Exception {

    /**
     * Log messages
     * @var array
     */
    protected $log;

    /**
     * Sets the log messages of the deploy action
     * @param array $log
     * @return null
     */
    public function setLog(array $log) {
        $this->log = $log;
    }

    /**
     * Gets the log messages of the deploy action
     * @return null
     */
    public function getLog() {
        return $this->log;
    }

}