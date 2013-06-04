<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Project data container
 */
class ProjectData extends Data {

    /**
     * Name of the term
     * @var string
     */
    public $name;

    /**
     * Repository of this project
     * @var string
     */
    public $repository;

    /**
     * State of this project
     * @var string
     */
    public $state;

    /**
     * Environments of this project
     * @var array
     */
    public $environments;

    /**
     * Gets a string representation of this data
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

}