<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Environment data container
 */
class EnvironmentData extends Data {

    /**
     * Project of the environment
     * @var null|integer|ProjectData
     */
    public $project;

    /**
     * Name of the environment
     * @var string
     */
    public $name;

    /**
     * Name of the mode
     * @var string
     */
    public $mode;

    /**
     * Branch in the repository
     * @var string
     */
    public $branch;

    /**
     * Path in the repository
     * @var string
     */
    public $path;

    /**
     * Revision to start
     * @var string
     */
    public $revision;

    /**
     * Servers of this environment
     * @var array
     */
    public $servers;

    /**
     * Gets a string representation of this data
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

}