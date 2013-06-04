<?php

namespace dbud\model\git;

/**
 * Data container for a commit file
 */
class GitCommitFile {

    /**
     * Path of the file
     * @var string
     */
    public $path;

    /**
     * Action for the file (create|delete)
     * @var string
     */
    public $action;

    /**
     * Mode of the file
     * @var string
     */
    public $mode;

}