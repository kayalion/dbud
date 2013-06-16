<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Server data container
 */
class ServerData extends Data {

    /**
     * Repository of this server
     * @var null|integer|RepositoryData
     */
    public $repository;

    /**
     * Name of the term
     * @var string
     */
    public $name;

    /**
     * Name of the protocol
     * @var string
     */
    public $protocol;

    /**
     * Remote host
     * @var string
     */
    public $remoteHost;

    /**
     * Remote port
     * @var string
     */
    public $remotePort;

    /**
     * Remote username
     * @var string
     */
    public $remoteUsername;

    /**
     * Remote password
     * @var string
     */
    public $remotePassword;

    /**
     * Flag to see if the SSH key should be used
     * @var boolean
     */
    public $useKey;

    /**
     * Flag to see if the passive FTP should be used
     * @var boolean
     */
    public $usePassive;

    /**
     * Flag to see if the FTP should be connected through SSL
     * @var boolean
     */
    public $useSsl;

    /**
     * Branch in the repository
     * @var string
     */
    public $branch;

    /**
     * Path in the repository
     * @var string
     */
    public $repositoryPath;

    /**
     * Deployed revision
     * @var string
     */
    public $revision;

    /**
     * Files to exclude
     * @var string
     */
    public $exclude;

    /**
     * Commands to execute
     * @var string
     */
    public $commands;

    /**
     * Gets a string representation of this data
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Gets the DSN of the server connection
     * @return string
     */
    public function getDsn() {
        return $this->protocol . '://' . $this->remoteUsername . ($this->useKey ? '' : ':*****') . '@' . $this->remoteHost . ':' . $this->remotePort . $this->remotePath;
    }

    /**
     * Gets the exclude
     * @return array
     */
    public function parseExclude() {
        if (!$this->exclude) {
            return array();
        }

        return explode("\n", $this->exclude);
    }

    /**
     * Gets the commands
     * @return array
     */
    public function parseCommands() {
        if (!$this->commands) {
            return array();
        }

        return explode("\n", $this->commands);
    }

    /**
     * Gets the revision in a friendly format
     * @return string
     */
    public function getFriendlyRevision() {
        return substr($this->revision, 0, 7);
    }

}