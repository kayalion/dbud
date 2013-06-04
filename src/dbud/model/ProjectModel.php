<?php

namespace dbud\model;

use dbud\model\data\ProjectData;
use dbud\model\git\GitRepository;

use zibo\library\filesystem\File;
use zibo\library\orm\model\behaviour\FieldSlugBehaviour;
use zibo\library\orm\model\GenericModel;

use \Exception;

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
     * State of a new project or a updated project
     * @var string
     */
    const STATE_TO_CLONE = 'clone';

    /**
     * State of a succesfully cloned project
     * @var string
     */
    const STATE_CLONED = "cloned";

    /**
     * State of a unsuccessfully cloned project
     * @var string
     */
    const STATE_ERROR = "error";

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
        $query->setRecursiveDepth(0);
        $query->addCondition('{slug} = %1%', $slug);

        return $query->queryFirst();
    }

    /**
     * Clones the repository of the project into the data directory
     * @param dbud\model\data\ProjectData $project
     * @return array Array with the name of the branch as key and value
     */
    public function getBranchesForProject($project) {
        if ($project->state != self::STATE_CLONED) {
            throw new Exception('Could not get the branches: state of project needs to be cloned');
        }

        $git = $this->getGitRepository($project);

        return $git->getBranches();
    }

    public function getRepositoryPath($project, $branch = null) {
        if ($project->state != self::STATE_CLONED) {
            throw new Exception('Could not get the repository path: state of project needs to be cloned');
        }

        $git = $this->getGitRepository($project, $branch);

        return $git->getLocalPath();
    }

    public function pullProject($project) {
        if ($project->state != self::STATE_CLONED) {
            throw new Exception('Could not get the branches: state of project needs to be cloned');
        }

        $git = $this->getGitRepository($project);
        $git->pullRepository();

        return $git->getLocalPath();
    }

    /**
     *
     * @param unknown_type $project
     * @param unknown_type $branch
     */
    public function pullBranch($project, $branch) {
        $masterPath = $this->pullProject($project);

        $git = $this->getGitRepository($project, $branch);

        $branchPath = $git->getLocalPath();

        $masterPath->copy($branchPath);

        $git->checkoutBranch($branch);
        $git->pullRepository();
    }

    /**
     *
     * @param unknown_type $project
     * @param unknown_type $branch
     * @param unknown_type $commit
     */
    public function getCommitLogs($project, $branch, $commit = null) {
        $git = $this->getGitRepository($project, $branch);

        return $git->getCommitLogs($commit);
    }

    /**
     * Clones the repository of the project into the data directory
     * @param dbud\model\data\ProjectData $project
     * @return null
     */
    public function cloneProject(ProjectData $project) {
        $logModel = $this->orm->getDbudLogModel();

        $git = $this->getGitRepository($project);
        try {
            if ($git->cloneRepository()) {
                $logModel->logMessage($project->id, 'Cloned ' . $project->repository);
            }

            $project->state = self::STATE_CLONED;
        } catch (Exception $e) {
            $logModel->logMessage($project->id, $e->getMessage());

            $project->state = self::STATE_ERROR;
        }

        $this->save($project, 'state');
    }

    /**
     * Gets the git bindings for a project
     * @param dbud\model\data\ProjectData $project
     * @return dbud\model\git\GitRepository
     */
    protected function getGitRepository(ProjectData $project, $branch = null) {
        $zibo = $this->orm->getZibo();

        $applicationDirectory = $zibo->getApplicationDirectory();
        if ($branch) {
            $repositoryDirectory = new File($applicationDirectory, 'data/dbud/project/' . $project->id . '/branch/' . $branch);
        } else {
            $repositoryDirectory = new File($applicationDirectory, 'data/dbud/project/' . $project->id . '/repository');
        }

        $git = new GitRepository($project->repository, $repositoryDirectory);
        $git->setLog($zibo->getLog());
        $git->setGitBinary($zibo->getParameter('system.binary.git', 'git'));

        return $git;
    }

    protected function deleteData($data) {
        $data = parent::deleteData($data);

        // delete logs
        $logModel = $this->orm->getDbudLogModel();
        $logModel->deleteProjectLogs($data->id);

        // delete project directory
        $zibo = $this->orm->getZibo();

        $applicationDirectory = $zibo->getApplicationDirectory();

        $projectDirectory = new File($applicationDirectory, 'data/dbud/project/' . $data->id);
        $projectDirectory->delete();

        return $data;
    }

}