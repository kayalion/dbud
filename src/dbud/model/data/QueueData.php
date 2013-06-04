<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Queue data container
 */
class QueueData extends Data {

    /**
     * Job of this queue
     * @var null|integer|JobData
     */
    public $job;

    /**
     * Data type
     * @var string
     */
    public $dataType;

    /**
     * Data id
     * @var string
     */
    public $dataId;

    /**
     * Description of the task
     * @var string
     */
    public $task;

}