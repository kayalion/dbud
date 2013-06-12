<?php

namespace dbud\model;

use dbud\model\data\RepositoryData;

use zibo\library\orm\model\behaviour\FieldSlugBehaviour;
use zibo\library\orm\model\behaviour\UniqueBehaviour;
use zibo\library\orm\model\GenericModel;

/**
 * Project model
 */
class RepositoryModel extends GenericModel {

    /**
     * Name of this model
     * @var string
     */
    const NAME = 'DbudRepository';

    /**
     * Initializes the model
     * @return null
     */
    protected function initialize() {
        $this->addBehaviour(new FieldSlugBehaviour('name'));
        $this->addBehaviour(new UniqueBehaviour('repository'));
    }

    /**
     * Gets all the repositories
     * @return null
     */
    public function getRepositories() {
        $query = $this->createQuery();
        $query->addOrderBy('{name} ASC');

        return $query->query();
    }

    /**
     * Gets a repository by its slug
     * @param string $slug
     * @return null|dbud\model\data\EnvironmentData
     */
    public function getRepositoryBySlug($slug) {
        $query = $this->createQuery();
        $query->setRecursiveDepth(0);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

    /**
     * Gets the GIT repository for a repository
     * @param dbud\model\data\RepositoryData $repository
     * @param string $branch
     * @return zibo\library\git\Repository
     */
    public function getGitRepository(RepositoryData $repository, $branch = null) {
        $zibo = $this->orm->getZibo();

        $git = $zibo->getDependency('zibo\\library\\git\\GitClient');
        $directoryData = $zibo->getParameter('dbud.directory.data');

        $directoryBranch = $repository->getBranchDirectory($directoryData, $branch);

        return $git->createRepository($directoryBranch);
    }

}