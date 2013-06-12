<?php

namespace dbud\controller;

use dbud\model\RepositoryModel;
use dbud\model\ServerModel;

use dbud\Module;

use zibo\app\view\BaseView;
use zibo\app\view\FormView;

use zibo\library\decorator\StorageSizeDecorator;
use zibo\library\dependency\exception\DependencyNotFoundException;
use zibo\library\dependency\exception\DependencyException;
use zibo\library\filesystem\File;
use zibo\library\html\Breadcrumbs;
use zibo\library\http\Response;
use zibo\library\orm\OrmManager;
use zibo\library\validation\exception\ValidationException;

/**
 * Controller of the repositories
 */
class RepositoryController extends AbstractController {

    /**
     * Action to show an overview od the repositories
     * @param OrmManager $orm
     * @return null
     */
    public function overviewAction(OrmManager $orm) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repositories = $repositoryModel->getRepositories();

        $view = $this->createView('dbud/repository.overview', 'dbud.title.repository.overview');
        $view->set('repositories', $repositories);

        $this->response->setView($view);
    }

    /**
     * Action to show an overview of the files in a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function filesAction(OrmManager $orm, $repository, $branch = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
        $directory = $this->zibo->getParameter('dbud.directory.data');

        $directoryHead = $repository->getBranchDirectory($directory);
        $repositoryHead = $git->createRepository($directoryHead);

        if (!$branch) {
            $branch = $repositoryHead->getBranch();

            $this->response->setRedirect($this->getUrl('dbud.repository.files', array('repository' => $repository->slug, 'branch' => $branch)));

            return;
        }

        $branches = $repositoryHead->getBranches();
        if (!isset($branches[$branch])) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $directoryBranch = $repository->getBranchDirectory($directory, $branch);
        $repositoryBranch = $git->createRepository($directoryBranch);

        $pathTokens = array_slice(func_get_args(), 3);
        $pathNormalized = ltrim(implode('/', $pathTokens), '/');

        $path = new File($directoryBranch, $pathNormalized);
        if (!$path->exists()) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $sizeDecorator = new StorageSizeDecorator();

        if (!$path->isDirectory()) {
            $view = $this->createView('dbud/repository.file', 'dbud.title.repository.detail', $repository);

            $extension = $path->getExtension();

            $isText = $this->zibo->getParameter('dbud.repository.extension.text.' . $extension);
            if ($isText) {
                $content = $path->read();
                if (!$content) {
                    $lines = 0;
                } else {
                    $lines = count(explode("\n", $content));
                }

                $view->addStyle('prettify/prettify.css');
                $view->addJavascript('prettify/prettify.js');
                $view->addInlineJavascript('prettyPrint();');
            } else {
                $content = null;
                $lines = false;
            }

            $view->set('lines', $lines);
            $view->set('size', $sizeDecorator->decorate($path->getSize()));
            $view->set('content', $content);
            $view->set('extension', $extension);
        } else {

            $files = $repositoryBranch->getTree($branch, $pathNormalized ? $pathNormalized . '/' : null);
            foreach ($files as $file) {
                $file->size = $sizeDecorator->decorate($file->size);
            }

            $view = $this->createView('dbud/repository.files', 'dbud.title.repository.detail', $repository);
            $view->set('files', $files);
        }

        $breadcrumbs = $this->createBreadcrumbs($pathTokens, 'dbud.repository.files', $repository, $branch);

        $view->set('breadcrumbs', $breadcrumbs);
        $view->set('branch', $branch);
        $view->set('branches', $branches);
        $view->set('path', rtrim('/' . implode('/', $pathTokens), '/'));
        $view->set('name', $path->getName());

        $this->response->setView($view);
    }

    /**
     * Action to download a file or directory from a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function downloadAction(OrmManager $orm, $repository, $branch) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
        $directory = $this->zibo->getParameter('dbud.directory.data');

        $directoryHead = $repository->getBranchDirectory($directory);
        $repositoryHead = $git->createRepository($directoryHead);

        $branches = $repositoryHead->getBranches();
        if (!isset($branches[$branch])) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $directoryBranch = $repository->getBranchDirectory($directory, $branch);
        $repositoryBranch = $git->createRepository($directoryBranch);

        $pathTokens = array_slice(func_get_args(), 3);
        $pathNormalized = ltrim(implode('/', $pathTokens), '/');

        $path = new File($directoryBranch, $pathNormalized);
        if (!$path->exists()) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if (!$path->isDirectory()) {
            $this->setDownloadView($path);

            return;
        }

        $file = File::getTemporaryFile();

        $archive = $this->zibo->getDependency('zibo\\library\\archive\\Archive', 'zip', array('file' => $file));
        $archive->compress($path);

        $this->setDownloadView($file, $path->getName() . '.zip', true);
    }

    /**
     * Action to show this commit history of a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function commitsAction(OrmManager $orm, $repository, $branch = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
        $directory = $this->zibo->getParameter('dbud.directory.data');

        $directoryHead = $repository->getBranchDirectory($directory);
        $repositoryHead = $git->createRepository($directoryHead);

        if (!$branch) {
            $branch = $repositoryHead->getBranch();

            $this->response->setRedirect($this->getUrl('dbud.repository.commits', array('repository' => $repository->slug, 'branch' => $branch)));

            return;
        }

        $branches = $repositoryHead->getBranches();
        if (!isset($branches[$branch])) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $directoryBranch = $repository->getBranchDirectory($directory, $branch);
        $repositoryBranch = $git->createRepository($directoryBranch);

        $pathTokens = array_slice(func_get_args(), 3);
        $pathNormalized = ltrim(implode('/', $pathTokens), '/');

        $commits = $repositoryBranch->getCommits($pathNormalized, 10);
        $breadcrumbs = $this->createBreadcrumbs($pathTokens, 'dbud.repository.commits', $repository, $branch);

        $view = $this->createView('dbud/repository.commits', 'dbud.title.repository.detail', $repository);
        $view->set('breadcrumbs', $breadcrumbs);
        $view->set('branch', $branch);
        $view->set('branches', $branches);
        $view->set('commits', $commits);

        $this->response->setView($view);
    }

    /**
     * Action to show this commit history of a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function commitAction(OrmManager $orm, $repository, $branch, $revision) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
        $directory = $this->zibo->getParameter('dbud.directory.data');

        $directoryHead = $repository->getBranchDirectory($directory);
        $repositoryHead = $git->createRepository($directoryHead);

        $branches = $repositoryHead->getBranches();
        if (!isset($branches[$branch])) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $directoryBranch = $repository->getBranchDirectory($directory, $branch);
        $repositoryBranch = $git->createRepository($directoryBranch);

        $commit = $repositoryBranch->getCommit($revision);

        $view = $this->createView('dbud/repository.commit', 'dbud.title.repository.detail', $repository);
        $view->set('branch', $branch);
        $view->set('branches', $branches);
        $view->set('commit', $commit);

        $this->response->setView($view);
    }

    /**
     * Action to show the integration overview
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function integrationAction(OrmManager $orm, $repository, $branch = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
        $directory = $this->zibo->getParameter('dbud.directory.data');

        $directoryHead = $repository->getBranchDirectory($directory);
        $repositoryHead = $git->createRepository($directoryHead);

        $branches = $repositoryHead->getBranches();
        if (!isset($branches[$branch])) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $builderModel = $orm->getDbudBuilderModel();
        $builders = $builderModel->getBuildersForRepository($repository->id, $branch);

        $view = $this->createView('dbud/repository.integration', 'dbud.title.repository.detail', $repository);
        $view->set('branch', $branch);
        $view->set('branches', $branches);
        $view->set('builders', $builders);

        $this->response->setView($view);
    }

    /**
     * Action to manually queue integration
     * @param OrmManager $orm
     * @param string $repository
     * @param string $branch
     * @return null
     */
    public function buildAction(OrmManager $orm, $repository, $branch) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverOptions = array();
        $data = array('servers' => array());

        $builderModel = $orm->getDbudBuilderModel();
        $builders = $builderModel->getBuildersForRepository($repository->id, $branch);
        if (!$builders) {
            $this->response->setRedirect($this->getUrl('dbud.repository.integration', array('repository' => $repository->slug, 'branch' => $branch)));

            return;
        }

        foreach ($builders as $builder) {
            $builderOptions[$builder->id] = $builder->name;
            $data['builders'][$builder->id] = $builder->id;
        }

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($data);
        $formBuilder->addRow('builders', 'option', array(
            'label' => $translator->translate('dbud.label.builders.build'),
            'description' => $translator->translate('dbud.label.builders.build.description'),
            'options' => $builderOptions,
            'multiselect' => true,
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.repository.integration', array('repository' => $repository->slug, 'branch' => $branch)));

                return;
            }

            $activityModel = $orm->getDbudActivityModel();

            $data = $form->getData();
            foreach ($data['builders'] as $builderId) {
                $builders[$builderId]->repository = $repository;

                $activityModel->queueBuild($builders[$builderId], true);
            }

            $this->response->setRedirect($this->getUrl('dbud.repository.activity', array('repository' => $repository->slug)));

            return;
        }

        $formAction = $this->request->getUrl();

        $git = $repositoryModel->getGitRepository($repository, $branch);
        $branches = $git->getBranches();

        $view = new FormView($form->getFormView(), $formAction, 'dbud/repository.build');
        $view->setPageTitle($translator->translate('dbud.title.repository.build'));
        $view->setPageSubTitle($repository->name);
        $view->set('repository', $repository);
        $view->set('branches', $branches);
        $view->set('branch', $branch);

        $this->response->setView($view);
    }

    /**
     * Action to show the deployment overview
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function deploymentAction(OrmManager $orm, $repository, $branch = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        $git = $this->zibo->getDependency('zibo\\library\\git\\GitClient');
        $directory = $this->zibo->getParameter('dbud.directory.data');

        $directoryHead = $repository->getBranchDirectory($directory);
        $repositoryHead = $git->createRepository($directoryHead);

        $branches = $repositoryHead->getBranches();
        if (!isset($branches[$branch])) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $securityManager = $this->getSecurityManager();
        $deployCode = substr($securityManager->encrypt($repository->id), 0, 7);

        $serverModel = $orm->getDbudServerModel();
        $servers = $serverModel->getServersForRepository($repository->id, $branch);

        $view = $this->createView('dbud/repository.deployment', 'dbud.title.repository.detail', $repository);
        $view->set('branch', $branch);
        $view->set('branches', $branches);
        $view->set('deployCode', $deployCode);
        $view->set('servers', $servers);

        $this->response->setView($view);
    }

    /**
     * Action to manually queue deployment
     * @param OrmManager $orm
     * @param string $repository
     * @param string $branch
     * @return null
     */
    public function deployAction(OrmManager $orm, $repository, $branch) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $serverOptions = array();
        $data = array('servers' => array());

        $serverModel = $orm->getDbudServerModel();
        $servers = $serverModel->getServersForRepository($repository->id, $branch);
        if (!$servers) {
            $this->response->setRedirect($this->getUrl('dbud.repository.deployment', array('repository' => $repository->slug, 'branch' => $branch)));

            return;
        }

        foreach ($servers as $server) {
            $serverOptions[$server->id] = $server->name;
            $data['servers'][$server->id] = $server->id;
        }

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($data);
        $formBuilder->addRow('servers', 'option', array(
            'label' => $translator->translate('dbud.label.servers.deploy'),
            'description' => $translator->translate('dbud.label.servers.deploy.description'),
            'options' => $serverOptions,
            'multiselect' => true,
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.repository.deployment', array('repository' => $repository->slug, 'branch' => $branch)));

                return;
            }

            $activityModel = $orm->getDbudActivityModel();

            $data = $form->getData();
            foreach ($data['servers'] as $serverId) {
                $servers[$serverId]->repository = $repository;

                $activityModel->queueDeploy($servers[$serverId]);
            }

            $this->response->setRedirect($this->getUrl('dbud.repository.activity', array('repository' => $repository->slug)));

            return;
        }

        $formAction = $this->request->getUrl();

        $git = $repositoryModel->getGitRepository($repository, $branch);
        $branches = $git->getBranches();

        $view = new FormView($form->getFormView(), $formAction, 'dbud/repository.deploy');
        $view->setPageTitle($translator->translate('dbud.title.repository.deploy'));
        $view->setPageSubTitle($repository->name);
        $view->set('repository', $repository);
        $view->set('branches', $branches);
        $view->set('branch', $branch);

        $this->response->setView($view);
    }

    /**
     * Action to automaticaly queue a repository update
     * @param OrmManager $orm
     * @param string $repository
     * @param string $slug
     * @return null
     */
    public function autoUpdateAction(OrmManager $orm, $repository, $code) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getById($id, 0);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $securityManager = $this->getSecurityManager();
        $deployCode = substr($securityManager->encrypt($repository->id), 0, 7);

        if ($code != $deployCode) {
            $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

            return;
        }

        $orm->getDbudActivityModel()->queueRepositoryUpdate($repository);
    }

    /**
     * Action to update a repository
     * @param OrmManager $orm
     * @param string $repository
     * @return null
     */
    public function updateAction(OrmManager $orm, $repository) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->setWorkingView($repository)) {
            return;
        }

        if ($this->request->isPost()) {
            if ($this->request->getBodyParameter('cancel')) {
                $referer = $this->request->getQueryParameter('referer');
                if (!$referer) {
                    $referer = $this->getUrl('dbud.repository.detail', array('repository' => $repository->slug));
                }

                $this->response->setRedirect($referer);

                return;
            }

            $orm->getDbudActivityModel()->queueRepositoryUpdate($repository);

            $this->response->setRedirect($this->getUrl('dbud.repository.activity', array('repository' => $repository->slug)));

            return;
        }

        $view = $this->createView('dbud/repository.update', 'dbud.title.repository.detail', $repository);

        $this->response->setView($view);
    }

    /**
     * Action to show the activity of a repository
     * @param OrmManager $orm
     * @param string $repository
     * @return null
     */
    public function activityAction(OrmManager $orm, $repository) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $url = $this->getUrl('dbud.activity') . '?page=1&repository=' . $repository->id;

        $this->response->setRedirect($url);
    }

    /**
     * Action to add or edit a repository
     * @param OrmManager $orm
     * @param integer $id
     * @return null
     */
    public function formAction(OrmManager $orm, $repository = null) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        if ($repository) {
            $repository = $repositoryModel->getRepositoryBySlug($repository);
            if (!$repository) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }
        } else {
            $repository = $repositoryModel->createData();
        }

        $translator = $this->getTranslator();

        $formBuilder = $this->createFormBuilder($repository);
        $formBuilder->addRow('name', 'string', array(
            'label' => $translator->translate('dbud.label.name'),
            'description' => $translator->translate('dbud.label.repository.name'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('repository', 'string', array(
            'label' => $translator->translate('dbud.label.repository'),
            'description' => $translator->translate('dbud.label.repository.description'),
            'attributes' => array(
                'class' => 'input-xxlarge',
            ),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form = $formBuilder->build($this->request);
        if ($form->isSubmitted()) {
            if ($this->request->getBodyParameter('cancel')) {
                if ($repository->id) {
                    $this->response->setRedirect($this->getUrl('dbud.repository.detail', array('repository' => $repository->slug)));
                } else {
                    $this->response->setRedirect($this->getUrl('dbud.repository.overview'));
                }

                return;
            }

            try {
                $form->validate();

                $data = $form->getData();
                $data->state = Module::STATE_WORKING;

                $repositoryModel->save($data);

                if (!$repository->id) {
                    $directory = $this->zibo->getParameter('dbud.directory.data');
                    $directoryHead = $data->getBranchDirectory($directory);
                    $directoryHead->create();
                }

                $orm->getDbudActivityModel()->queueRepositoryInit($data);

                $this->response->setRedirect($this->getUrl('dbud.repository.activity', array('repository' => $data->slug)));

                return;
            } catch (ValidationException $exception) {
                $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

                $form->setValidationException($exception);
            }
        }

        if ($repository->id) {
            $formAction = $this->getUrl('dbud.repository.edit.submit', array('repository' => $repository->slug));
        } else {
            $formAction = $this->getUrl('dbud.repository.add.submit');
        }

        $view = new FormView($form->getFormView(), $formAction);

        if ($repository->id) {
            $view->setPageTitle($translator->translate('dbud.title.repository.edit'));
            $view->setPageSubTitle($repository->name);
        } else {
            $view->setPageTitle($translator->translate('dbud.title.repository.add'));
        }

        $this->response->setView($view);
    }

    /**
     * Action to delete a repository
     * @param OrmManager $orm
     * @param string $repository
     * @return null
     */
    public function deleteAction(OrmManager $orm, $repository) {
        $repositoryModel = $orm->getDbudRepositoryModel();
        $repository = $repositoryModel->getRepositoryBySlug($repository);
        if (!$repository) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($this->request->isPost()) {
            if ($this->request->getBodyParameter('cancel')) {
                $this->response->setRedirect($this->getUrl('dbud.repository.detail', array('repository' => $repository->slug)));

                return;
            }

            $repositoryModel->delete($repository);

            $this->response->setRedirect($this->getUrl('dbud.repository.overview'));

            return;
        }

        $view = $this->createView('dbud/repository.delete', 'dbud.title.repository.detail', $repository);

        $this->response->setView($view);
    }

    /**
     * Creates breadcrumbs out of the path tokens
     * @param array $tokens Path tokens
     * @param string $url Id of the URL
     * @param dbud\model\data\RepositoryData $repository Repository data
     * @param string $branch Name of the branch
     * @return zibo\library\html\Breadcrumbs
     */
    protected function createBreadcrumbs(array $tokens, $url, $repository, $branch) {
        $url = $this->getUrl($url, array('repository' => $repository->slug, 'branch' => $branch));

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->addBreadcrumb($url, $repository->name);

        $breadcrumbPath = '';
        foreach ($tokens as $pathToken) {
            $breadcrumbPath .= '/' . $pathToken;

            $breadcrumbs->addBreadcrumb($url . $breadcrumbPath, $pathToken);
        }

        return $breadcrumbs;
    }

}