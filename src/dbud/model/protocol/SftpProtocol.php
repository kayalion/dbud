<?php

namespace dbud\model\protocol;

use dbud\model\data\ServerData;
use dbud\model\exception\DeployException;

use zibo\library\filesystem\File;
use zibo\library\form\FormBuilder;
use zibo\library\i18n\translation\Translator;
use zibo\library\validation\exception\ValidationException;
use zibo\library\validation\ValidationError;

/**
 * Interface for a deployment type
 */
class SftpProtocol extends AbstractSshProtocol {

    /**
     * Creates the rows needed for this protocol
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @return null
     */
    public function createForm(FormBuilder $formBuilder, Translator $translator) {
        $this->createRepositoryRows($formBuilder, $translator, true);
        $this->createServerRows($formBuilder, $translator, 22, true, true, false, false);

        $formBuilder->addRow('exclude', 'text', array(
            'label' => $translator->translate('dbud.label.exclude'),
            'description' => $translator->translate('dbud.label.exclude.description'),
            'filters' => array(
                'trim' => array('trim.lines' => true, 'trim.empty' => true),
            ),
        ));

        $formBuilder->addRow('commands', 'text', array(
            'label' => $translator->translate('dbud.label.commands.post.deploy'),
            'description' => $translator->translate('dbud.label.commands.post.deploy.description'),
            'filters' => array(
                'trim' => array('trim.lines' => true, 'trim.empty' => true),
            ),
            'attributes' => array(
                'class' => 'console',
                'rows' => 5,
            ),
        ));
    }

    /**
     * Processes the server to validate the connection
     * @param dbud\model\data\ServerData $server
     * @return null
     * @throws zibo\library\validation\exception\ValidationException
     */
    public function processForm(ServerData $server) {
        $this->connect($server);

        if (!$this->ssh->fileExists($server->remotePath)) {
            $exception = new ValidationException();
            $exception->addError('remotePath', new ValidationError('error.remote.path.exists', 'Remote path does not exist'));

            throw $exception;
        }

        $this->ssh->disconnect();
    }

    /**
     * Processes the server to validate the connection
     * @param dbud\model\data\ServerData $server
     * @param zibo\library\filesystem\File $path Local path of the fileset
     * @param array $files Array with the path of the file as key and a git
     * commit file as value
     * @return array
     * @see dbud\model\git\GitCommitFile
     */
    public function deploy(ServerData $server, File $path, array $files) {
        $log = array();

        if (!$files) {
            return $log;
        }

        $this->connect($server);

        $localPath = $path->getAbsolutePath() . '/';
        $remotePath = rtrim($server->remotePath, '/') . '/';

        foreach ($files as $file => $action) {
            $remoteFile = $remotePath . $file;

            if ($action == 'D') {
                if ($this->ssh->deleteFile($remoteFile) === false) {
                    $log['-' . $file] = 'Could not delete ' . $remoteFile;

                    $this->ssh->disconnect();

                    $exception = new DeployException();
                    $exception->setLog($log);

                    throw $exception;
                }

                $log['-' . $file] = true;
            } else {
                $localFile = new File($localPath, $file);

                if ($this->ssh->uploadFile($localPath . $file, $remoteFile, $localFile->getPermissions()) === false) {
                    $log['+' . $file] = 'Could not upload ' . $file;

                    $this->ssh->disconnect();

                    $exception = new DeployException();
                    $exception->setLog($log);

                    throw $exception;
                }

                $log['+' . $file] = true;
            }
        }

        $commands = $server->parseCommands();
        foreach ($commands as $command) {
            try {
                $log[$command] = $this->ssh->execute($command);
            } catch (RuntimeSshException $e) {
                $log[$command] = $e->getMessage();

                $this->ssh->disconnect();

                $exception = new DeployException();
                $exception->setLog($log);

                throw $exception;
            }
        }

        $this->ssh->disconnect();

        return $log;
    }

}