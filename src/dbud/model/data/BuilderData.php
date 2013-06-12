<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Repository data container
 */
class BuilderData extends Data {

    /**
     * Repository of this builder
     * @var integer|RepositoryData
     */
    public $repository;

    /**
     * Name of the branch in the repository
     * @var string
     */
    public $branch;

    /**
     * Name of this builder
     * @var string
     */
    public $name;

    /**
     * State of this builder
     * @var string
     */
    public $state;

    /**
     * Flag to see if the repository should be copied to the working dir
     * @var boolean
     */
    public $copyRepository;

    /**
     * Script to run
     * @var string
     */
    public $script;

    /**
     * Built revision
     * @var string
     */
    public $revision;

    /**
     * Date last built
     * @var integer
     */
    public $dateBuilt;

    /**
     * Gets a string representation of this data
     * @return string
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Gets the revision in a friendly format
     * @return string
     */
    public function getFriendlyRevision() {
        return substr($this->revision, 0, 7);
    }

}