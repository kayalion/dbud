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
class SshProtocol extends AbstractSshProtocol {

    /**
     * Creates the rows needed for this protocol
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @return null
     */
    public function createForm(FormBuilder $formBuilder, Translator $translator) {
        $this->createServerRows($formBuilder, $translator, 22, false, true, false, false);

        $formBuilder->addRow('commands', 'text', array(
            'label' => $translator->translate('dbud.label.commands.deploy'),
            'description' => $translator->translate('dbud.label.commands.deploy.description'),
            'filters' => array(
                'trim' => array('trim.lines' => true, 'trim.empty' => true),
            ),
            'validators' => array(
                'required' => array(),
            ),
            'attributes' => array(
                'rows' => 10,
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
        $this->disconnect();
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

        $this->connect($server);

        $commands = $server->parseCommands();
        $command = implode("; ", $commands);

        $log['@' . $command] = $this->execute($command);

        $this->disconnect();

        return $log;
    }

}