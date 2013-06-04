<?php

namespace dbud\model\git;

/**
 * Data container for a commit log
 */
class GitCommitLog {

    /**
     * Id of the commit
     * @var string
     */
    public $id;

    /**
     * Author of the commit
     * @var string
     */
    public $author;

    /**
     * Date of the commit
     * @var string
     */
    public $date;

    /**
     * Commit message
     * @var string
     */
    public $message;

    /**
     * Modified files
     * @var array
     */
    public $files = array();

}