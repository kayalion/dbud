<?php

namespace dbud\model\protocol;

use dbud\model\data\ServerData;
use dbud\model\exception\DeployException;

use zibo\library\filesystem\File;
use zibo\library\form\FormBuilder;
use zibo\library\ftp\exception\FtpException;
use zibo\library\ftp\FtpClient;
use zibo\library\i18n\translation\Translator;
use zibo\library\validation\exception\ValidationException;
use zibo\library\validation\ValidationError;

use \Exception;

/**
 * Interface for a deployment type
 */
class FtpProtocol extends AbstractProtocol {

    /**
     * Constructs a new SSH protocol
     * @throws Exception when the SSH2 functions are not available
     */
    public function __construct() {
        if (!class_exists('zibo\\library\\ftp\\FtpClient')) {
            throw new Exception('Could not initialize the FTP protocol: install zibo.ftp module');
        }
    }

    /**
     * Creates the rows needed for this protocol
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @return null
     */
    public function createForm(FormBuilder $formBuilder, Translator $translator) {
        $this->createRepositoryRows($formBuilder, $translator, true);
        $this->createServerRows($formBuilder, $translator, 21, true, false, true, true);

        $formBuilder->addRow('exclude', 'text', array(
            'label' => $translator->translate('dbud.label.exclude'),
            'description' => $translator->translate('dbud.label.exclude.description'),
            'filters' => array(
                'trim' => array('trim.lines' => true, 'trim.empty' => true),
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
        try {
            $client = $this->getFtpClient($server);
        } catch (FtpException $e) {
            $exception = new ValidationException();
            $exception->addError('remoteHost', new ValidationError('error.remote.host.connect', $e->getMessage(), array('host' => $server->remoteHost . ':' . $server->remotePort)));

            throw $exception;
        }

        try {
            $client->getFiles($server->remotePath);
        } catch (FtpException $e) {
            $exception = new ValidationException();
            $exception->addError('remotePath', new ValidationError('error.remote.path.exists', 'Remote path does not exist'));

            throw $exception;
        }

        $client->disconnect();
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

        $client = $this->getFtpClient($server);

        $localPath = $path->getAbsolutePath() . '/';
        $remotePath = rtrim($server->remotePath, '/') . '/';

        foreach ($files as $file => $action) {
            $remoteFile = $remotePath . $file;

            if ($action == 'D') {
                try {
                    $client->deleteFile($remoteFile);

                    $log['-' . $file] = true;
                } catch (FtpException $exception) {
                    $log['-' . $file] = $exception->getMessage();

                    $client->disconnect();

                    $exception = new DeployException();
                    $exception->setLog($log);

                    throw $exception;
                }
            } else {
                try {
                    $localFile = new File($path, $file);

                    $client->createDirectory(dirname($remoteFile));
                    $client->put($localFile, $remoteFile, $localFile->getPermissions());

                    $log['+' . $file] = true;
                } catch (FtpException $exception) {
                    $log['+' . $file] = $exception->getMessage();

                    $client->disconnect();

                    $exception = new DeployException();
                    $exception->setLog($log);

                    throw $exception;
                }
            }
        }

        $client->disconnect();

        return $log;
    }

    /**
     * Gets the instance of the FTP client for the provided server
     * @param dbud\model\data\ServerData $server
     * @return zibo\library\ftp\FtpClient
     */
    protected function getFtpClient(ServerData $server) {
        $password = $this->securityManager->decrypt($server->remotePassword);

        $client = new FtpClient($server->remoteHost, $server->remoteUsername, $password, $server->remotePort, $server->useSsl);

        $client->connect();
        $client->setPassiveMode($server->usePassive);

        return $client;
    }

}