<?php

namespace dbud\model\protocol;

use dbud\model\data\ServerData;

use zibo\library\security\SecurityManager;
use zibo\library\ssh\authentication\PasswordSshAuthentication;
use zibo\library\ssh\authentication\PublicKeySshAuthentication;
use zibo\library\ssh\exception\AuthenticationSshException;
use zibo\library\ssh\exception\SshException;
use zibo\library\ssh\SshClient;
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
     * SSH client
     * @var zibo\library\ssh\SshClient
     */
    protected $ssh;

    /**
     * Constructs a new SSH protocol
     * @throws Exception when the SSH2 functions are not available
     */
    public function setSshClient($sshClient) {
        $this->ssh = $sshClient;
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
        if ($server->useKey) {
            if (!$this->publicKeyFile) {
                throw new Exception('No SSH public key file set');
            }

            if (!$this->privateKeyFile) {
                throw new Exception('No SSH private key file set');
            }

            if ($this->privateKeyPassphrase) {
                $passphrase = $this->securityManager->decrypt($this->privateKeyPassphrase);
            } else {
                $passphrase = null;
            }

            $authentication = new PublicKeySshAuthentication();
            $authentication->setPublicKeyFile($this->publicKeyFile);
            $authentication->setPrivateKeyFile($this->privateKeyFile);
            $authentication->setPrivateKeyPassphrase($passphrase);
        } else {
            $authentication = new PasswordSshAuthentication();
            $authentication->setPassword($this->securityManager->decrypt($server->remotePassword));
        }

        $authentication->setUsername($server->remoteUsername);

        $this->ssh->setAuthentication($authentication);

        try {
            $this->ssh->connect($server->remoteHost, $server->remotePort);
        } catch (AuthenticationSshException $exception) {
            if ($server->useKey) {
                $exception = new ValidationException();
                $exception->addError('remoteUsername', new ValidationError('error.remote.host.authenticate.key', 'Could not authenticate with SSH key'));

                throw $exception;
            } else {
                $error = new ValidationError('error.remote.host.authenticate.password', 'Could not authenticate with password');
                $exception = new ValidationException();
                $exception->addError('remoteUsername', $error);
                $exception->addError('remotePassword', $error);

                throw $exception;
            }
        } catch (SshException $exception) {
            $error = new ValidationError('error.remote.host.connect', 'Could not connect to %host%', array('host' => $server->remoteHost . ':' . $server->remotePort));
            $exception = new ValidationException();
            $exception->addError('remoteHost', $error);
            $exception->addError('remotePort', $error);

            throw $exception;
        }
    }

}