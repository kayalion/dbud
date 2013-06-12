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
     * Gets the servers of a repository
     * @return array
     */
    public function getServersForRepository($repositoryId, $branch = null) {
        $query = $this->createQuery();
        $query->addCondition('{repository} = %1%', $repositoryId);

        if ($branch) {
            $query->addCondition('{branch} = %1%', $branch);
        }

        $query->addOrderBy('{name} ASC');

        return $query->query();
    }

    /**
     * Gets a server by its slug
     * @param string $slug
     * @return null|dbud\model\data\ServerData
     */
    public function getServerBySlug($slug) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

}