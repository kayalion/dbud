<?php

namespace dbud\model\protocol;

use dbud\model\data\ServerData;

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
        $this->createRepositoryRows($formBuilder, $translator, true, true);
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

        foreach ($files as $file) {
            $remoteFile = $remotePath . $file->path;

            switch ($file->action) {
                case 'delete':
                    $log['-' . $file->path] = $this->ssh->deleteFile($remoteFile);;

                    break;
                case 'create':
                    $log['+' . $file->path] = $this->ssh->uploadFile($localPath . $file->path, $remoteFile, $file->mode);

                    break;
            }
        }

        $commands = $server->parseCommands();
        foreach ($commands as $command) {
            $log['@' . $command] = $this->ssh->execute($command);
        }

        $this->ssh->disconnect();

        return $log;
    }

}