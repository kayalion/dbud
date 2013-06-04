<?php

namespace dbud\model;

use zibo\library\orm\model\behaviour\FieldSlugBehaviour;
use zibo\library\orm\model\GenericModel;

/**
 * Server model
 */
class ServerModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudServer';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new FieldSlugBehaviour('name'));
    }

    /**
     * Gets the servers of a environment
     * @return array
     */
    public function getServersForEnvironment($environmentId) {
        $query = $this->createQuery();
        $query->addCondition('{environment} = %1%', $environmentId);
        $query->addOrderBy('{name} ASC');

        return $query->query();
    }

    /**
     * Gets a environment by its slug
     * @param string $slug
     * @return null|dbud\model\data\EnvironmentData
     */
    public function getServerBySlug($slug) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

}