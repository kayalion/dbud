<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Flow data container
 */
class FlowData extends Data {

    /**
     * Project of this flow object
     * @var integer|dbud\model\data\ProjectData
     */
    public $project;

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
     * Top position of this block
     * @var string
     */
    public $positionTop;

    /**
     * Left position of this block
     * @var string
     */
    public $positionLeft;

    /**
     * Previous flow object
     * @var integer|dbud\model\data\FlowData
     */
    public $previous;

    /**
     * Gets the element id for this flow object
     * @return string
     */
    public function getElementId() {
        if ($this->dataType == 'DbudServer') {
            return 'server-' . $this->dataId;
        } elseif ($this->dataType == 'DbudBuilder') {
            return 'builder-' . $this->dataId;
        }
    }

}