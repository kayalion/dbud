<?php

namespace dbud\model\data;

use zibo\library\filesystem\File;
use zibo\library\orm\model\data\Data;

/**
 * Repository data container
 */
class RepositoryData extends Data {

    /**
     * Name for this repository
     * @var string
     */
    public $name;

    /**
     * URL to the repository
     * @var string
     */
    public $repository;

    /**
     * Description of this repository
     * @var string
     */
    public $description;

    /**
     * State of this repository
     * @var string
     */
    public $state;

    /**
     * Gets a string representation of this data
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Gets the data directory for the provided branch
     * @param string|zibo\library\filesystem\File $directory Base data directory
     * @param string $branch Name of the branch
     * @return zibo\library\filesystem\File
     */
    public function getBranchDirectory($directory, $branch = null) {
        if ($branch == null) {
            $branch = 'HEAD';
        }

        return new File($directory, $this->id . '/' . $branch);
    }

}