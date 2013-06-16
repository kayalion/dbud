<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Project data container
 */
class ProjectData extends Data {

    /**
     * Name of the project
     * @var string
     */
    public $name;

    /**
     * Repositories of this project
     * @var array
     */
    public $repositories;

    /**
     * Flow of this project
     * @var array
     */
    public $flow;

    /**
     * Slug of this project
     * @var string
     */
    public $slug;

    /**
     * Gets a string representation of this data
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

}