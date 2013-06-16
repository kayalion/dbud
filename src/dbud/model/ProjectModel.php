<?php

namespace dbud\model;

use zibo\library\orm\model\behaviour\FieldSlugBehaviour;
use zibo\library\orm\model\GenericModel;

/**
 * Project model
 */
class ProjectModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudProject';

    /**
     * Initialize this model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new FieldSlugBehaviour('name'));
    }

    /**
     * Gets all the projects
     * @return null
     */
    public function getProjects() {
        $query = $this->createQuery();
        $query->addOrderBy('{name} ASC');

        return $query->query();
    }

    /**
     * Gets a project by its slug
     * @param string $slug
     * @return null|dbud\model\data\ProjectData
     */
    public function getProjectBySlug($slug) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(1);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

}