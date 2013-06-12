<?php

namespace dbud\model;

use zibo\library\orm\model\behaviour\FieldSlugBehaviour;
use zibo\library\orm\model\GenericModel;

/**
 * Builder model
 */
class BuilderModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudBuilder';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new FieldSlugBehaviour('name'));
    }

    /**
     * Gets the builders of a repository
     * @return array
     */
    public function getBuildersForRepository($repositoryId, $branch = null) {
        $query = $this->createQuery();
        $query->addCondition('{repository} = %1%', $repositoryId);

        if ($branch) {
            $query->addCondition('{branch} = %1%', $branch);
        }

        $query->addOrderBy('{name} ASC');

        return $query->query();
    }

    /**
     * Gets a builder by its slug
     * @param string $slug
     * @return null|dbud\model\data\BuilderData
     */
    public function getBuilderBySlug($slug) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

}