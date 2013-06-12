<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Activity data container
 */
class ActivityData extends Data {

    /**
     * Repository of this activity
     * @var integer|dbud\model\data\RepositoryData
     */
    public $repository;

    /**
     * Data type of the trigger object
     * @var string
     */
    public $dataType;

    /**
     * Data id of the trigger object
     * @var string
     */
    public $dataId;

    /**
     * Activity description
     * @var string
     */
    public $description;

    /**
     * Type of the message
     * @var string
     */
    public $state;

    /**
     * Queue job of this activity
     * @var integer|zibo\library\queue\model\data\QueueData
     */
    public $job;

    /**
     * Teaser of the activity message
     * @var string
     */
    protected $displayTeaser;

    /**
     * Detail of the activity message
     * @var string
     */
    protected $displayDescription;

    /**
     * Gets the teaser of this activity
     * @return null|string
     */
    public function getDisplayTeaser() {
        if (!isset($this->displayTeaser)) {
            $this->parseDescription();
        }

        return $this->displayTeaser;
    }

    /**
     * Gets the description of this activity
     * @return null|string
     */
    public function getDisplayDescription() {
        if (!isset($this->displayTeaser)) {
            $this->parseDescription();
        }

        return $this->displayDescription;
    }

    /**
     * Parses the teaser and description from the message
     * @return null
     */
    protected function parseDescription() {
        if (strpos($this->description, "\n") === false) {
            $this->displayTeaser = $this->description;
            $this->displayDescription = null;
        } else {
            list($this->displayTeaser, $this->displayDescription) = explode("\n", $this->description, 2);
        }

        $this->displayTeaser = htmlentities($this->displayTeaser);
        $this->displayDescription = nl2br(htmlentities(trim($this->displayDescription)));
    }

}