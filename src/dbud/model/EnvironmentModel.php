<?php

namespace dbud\model;

use zibo\library\orm\model\behaviour\FieldSlugBehaviour;
use zibo\library\orm\model\GenericModel;

/**
 * Environment model
 */
class EnvironmentModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudEnvironment';

    /**
     * Mode for manual deployment
     * @var string
     */
    const MODE_MANUAL = 'manual';

    /**
     * Mode for automatic deployment
     * @var string
     */
    const MODE_AUTOMATIC = 'auto';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new FieldSlugBehaviour('name'));
    }

    /**
     * Gets the environments of a project
     * @return array
     */
    public function getEnvironmentsForProject($projectId) {
        $query = $this->createQuery();
        $query->addCondition('{project} = %1%', $projectId);
        $query->addOrderBy('{name} ASC');

        return $query->query();
    }

    /**
     * Gets a environment by its slug
     * @param string $slug
     * @return null|dbud\model\data\EnvironmentData
     */
    public function getEnvironmentBySlug($slug) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

}