<?php

namespace dbud\model\data;

use zibo\library\orm\model\data\Data;

/**
 * Log data container
 */
class LogData extends Data {

    /**
     * Project of this log
     * @var null|integer|ProjectData
     */
    public $project;

    /**
     * Log message
     * @var string
     */
    public $message;

}