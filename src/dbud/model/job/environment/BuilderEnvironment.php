<?php

namespace dbud\model\job\environment;

use dbud\model\data\BuilderData;

/**
 * Interface for the script environment of the builder
 */
interface BuilderEnvironment {

    /**
     * Sets the builder and revision
     * @param dbud\model\data\BuilderData $builder
     * @param string $revision
     * @return null
     */
    public function setBuilder(BuilderData $builder, $revision);

    /**
     * Runs the builder
     * @return string Log of the builder
     */
    public function runBuilder();

    /**
     * Gets the exception of the last run
     * @return Exception
     */
    public function getException();

}