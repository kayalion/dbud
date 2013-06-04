<?php

namespace dbud\model\protocol;

use dbud\model\data\ServerData;

use zibo\library\security\SecurityManager;
use zibo\library\validation\exception\ValidationException;
use zibo\library\validation\ValidationError;

use \Exception;

/**
 * Abstract implementation for a SSH deployment type
 */
abstract class AbstractSshProtocol extends AbstractProtocol {

    /**
     * Path to the public key
     * @var string
     */
    protected $publicKeyFile;

    /**
     * Path to the private key
     * @var string
     */
    protected $privateKeyFile;

    /**
     * Path to the private passphrase
     * @var string
     */
    protected $privateKeyPassphrase;

    /**
     * SSH session
     * @var resource
     */
    protected $ssh;

    /**
     * SSH SFTP session
     * @var resource
     */
    protected $sftp;

    /**
     * Constructs a new SSH protocol
     * @throws Exception when the SSH2 functions are not available
     */
    public function __construct() {
        if (!function_exists('ssh2_connect')) {
            throw new Exception('Could not initialize the SSH protocol: install the SSH2 PHP bindings');
        }
    }

    /**
     * Sets the SSH public key
     * @param string $publicKeyFile Path to the public key file
     * @return null
     */
    public function setPublicKeyFile($publicKeyFile) {
        $this->publicKeyFile = $publicKeyFile;
    }

    /**
     * Sets the SSH private key
     * @param string $privateKeyFile Path to the private key file
     * @return null
     */
    public function setPrivateKeyFile($privateKeyFile) {
        $this->privateKeyFile = $privateKeyFile;
    }

    /**
     * Sets the SSH private key passphrase
     * @param string $passphrase Passphrase of the private key
     * @return null
     */
    public function setPrivateKeyPassphrase($privateKeyPassphrase) {
        $this->privateKeyPassphrase  = $privateKeyPassphrase;
    }

    /**
     * Processes the server to validate the connection
     * @param dbud\model\data\ServerData $server
     * @return null
     * @throws zibo\library\validation\exception\ValidationException
     */
    protected function connect(ServerData $server) {
        $host = $server->remoteHost;
        $port = $server->remotePort ? $server->remotePort : 22;

        $this->ssh = @ssh2_connect($host, $port);
        if (!$this->ssh) {
            $exception = new ValidationException();
            $exception->addError('remoteHost', new ValidationError('error.remote.host.connect', 'Could not connect to %host%', array('host' => $host . ':' . $port)));

            throw $exception;
        }

        if ($server->useKey) {
            if (!$this->publicKeyFile) {
                throw new Exception('No SSH public key file set');
            }

            if (!$this->privateKeyFile) {
                throw new Exception('No SSH private key file set');
            }

            if (!@ssh2_auth_pubkey_file($this->ssh, $server->remoteUsername, $this->publicKeyFile, $this->privateKeyFile, $this->privateKeyPassphrase)) {
                $exception = new ValidationException();
                $exception->addError('remoteUsername', new ValidationError('error.remote.host.authenticate.key', 'Could not authenticate with SSH key'));

                $this->ssh = null;

                throw $exception;
            }
        } else {
            $password = $this->securityManager->decrypt($server->remotePassword);

            if (!@ssh2_auth_password($this->ssh, $server->remoteUsername, $password)) {
                $exception = new ValidationException();
                $exception->addError('remoteUsername', new ValidationError('error.remote.host.authenticate.password', 'Could not authenticate with password'));

                $this->ssh = null;

                throw $exception;
            }
        }
    }

    /**
     * Executes a command on the remote server
     * @param string $command Command to execute
     * @return string output of the command
     * @throws Exception
     */
    protected function execute($command) {
        if (!$this->ssh) {
            throw new Exception('Could not check the file status: not connected');
        }

        $stream = ssh2_exec($this->ssh, $command);
        stream_set_blocking($stream, true);

        $output = stream_get_contents($stream);

        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($errorStream, true);

        $output .= stream_get_contents($errorStream);

        fclose($errorStream);
        fclose($stream);

        return $output;
    }

    /**
     * Checks if a file or directory exists
     * @param string $path
     * @return boolean
     */
    protected function fileExists($path) {
        if (!$this->ssh) {
            throw new Exception('Could not check the file status: not connected');
        }

        if (!$this->sftp) {
            $this->sftp = ssh2_sftp($this->ssh);
        }

        $stat = @ssh2_sftp_stat($this->sftp, $path);
        if (!$stat) {
            return false;
        }

        return true;
    }

    /**
     * Creates a directory on the remote server
     * @param string $path
     * @return boolean
     */
    protected function createDirectory($path) {
        $parent = dirname($path);
        if (!$this->fileExists($parent)) {
            if (!$this->createDirectory($parent)) {
                throw new Exception('Could not create ' . $parent);
            }
        }

        return @ssh2_sftp_mkdir($this->sftp, $path);
    }

    /**
     * Copies a file to the remote server
     * @param string $source Local path of the file
     * @param string $destination Remote path of the file
     * @return boolean True on success, false otherwise
     */
    protected function copyFile($source, $destination, $mode = 0644) {
        if (!$this->fileExists($destination)) {
            $this->createDirectory(dirname($destination));
        }

        $stream = fopen('ssh2.sftp://' . $this->sftp . $destination, 'w');
        try {
            if (!$stream) {
                throw new Exception("Could not open remote file: $destination");
            }

            $data = file_get_contents($source);

            if ($data === false) {
                throw new Exception("Could not open local file: $source.");
            }

            if (fwrite($stream, $data) === false) {
                throw new Exception("Could not send data from file: $source.");
            }

            fclose($stream);
        } catch (Exception $e) {
            fclose($stream);

            return false;
        }

        return true;
    }

    /**
     * Deletes a remote file
     * @param string $path Remote path of the file
     * @return boolean True on success, false otherwise
     */
    protected function deleteFile($path) {
        if (!$this->fileExists($path)) {
            return true;
        }

        return @ssh2_sftp_unlink($this->sftp, $path);
    }

    /**
     * Changes the mode of a remote file
     * @param string $path Remote path of the file
     * @param integer $mode Mode of the file
     * @return boolean True on success, false otherwise
     */
    protected function chmod($path, $mode) {
        if (!$this->fileExists($path)) {
            return false;
        }

        return @ssh2_sftp_chmod($this->sftp, $path, $mode);
    }

    /**
     * Disconnects the SSH connection
     * @return null
     */
    protected function disconnect() {
        $this->ssh = null;
        $this->sftp = null;
    }

}