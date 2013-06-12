<?php

namespace dbud\model\protocol;

use zibo\core\Zibo;

use zibo\library\form\FormBuilder;
use zibo\library\i18n\translation\Translator;

/**
 * Abstract implementation for a deployment type
 */
abstract class AbstractProtocol implements Protocol {

    /**
     * Instance of Zibo
     * @var zibo\core\Zibo
     */
    protected $zibo;

    /**
     * Instance of the security manager
     * @var zibo\library\security\SecurityManager
     */
    protected $securityManager;

    /**
     * Sets the instance of Zibo to this protocol
     * @param zibo\core\Zibo $zibo
     * @return null
     */
    public function setZibo(Zibo $zibo) {
        $this->zibo = $zibo;
    }

    /**
     * Sets the security manager
     * @param zibo\library\security\SecurityManager $securityManager
     * @return null
     */
    public function setSecurityManager($securityManager) {
        $this->securityManager = $securityManager;
    }

    /**
     * Creates the rows needed for the repository
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @return null
     */
    protected function createRepositoryRows(FormBuilder $formBuilder, Translator $translator, $addRepositoryPath) {
        $formBuilder->addRow('revision', 'string', array(
            'label' => $translator->translate('dbud.label.revision'),
            'description' => $translator->translate('dbud.label.revision.description'),
            'attributes' => array(
                'class' => 'input-xxlarge',
            ),
            'filters' => array(
                'trim' => array(),
            ),
        ));

        if ($addRepositoryPath) {
            $formBuilder->addRow('repositoryPath', 'string', array(
                'label' => $translator->translate('dbud.label.path.repository'),
                'description' => $translator->translate('dbud.label.path.repository.description'),
                'filters' => array(
                    'trim' => array(),
                ),
                'validators' => array(
                    'required' => array(),
                ),
            ));
        }
    }

    /**
     * Creates the rows needed for a server connection
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @param integer $defaultPort
     * @param boolean $addRemotePath
     * @param boolean $addUseKey
     * @return null
     */
    protected function createServerRows(FormBuilder $formBuilder, Translator $translator, $defaultPort, $addRemotePath, $addUseKey, $addUsePassive, $addUseSsl) {
        $formBuilder->addRow('remoteHost', 'string', array(
            'label' => $translator->translate('dbud.label.host'),
            'description' => $translator->translate('dbud.label.host.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('remotePort', 'string', array(
            'label' => $translator->translate('dbud.label.port'),
            'description' => $translator->translate('dbud.label.port.description'),
            'default' => $defaultPort,
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'minmax' => array('required' => false, 'minimum' => 1, 'maximum' => 65535),
            ),
        ));

        if ($addRemotePath) {
            $formBuilder->addRow('remotePath', 'string', array(
                'label' => $translator->translate('dbud.label.path.remote'),
                'description' => $translator->translate('dbud.label.path.remote.description'),
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
        }

        $formBuilder->addRow('remoteUsername', 'string', array(
            'label' => $translator->translate('dbud.label.username'),
            'description' => $translator->translate('dbud.label.username.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('newPassword', 'password', array(
            'label' => $translator->translate('dbud.label.password'),
            'description' => $translator->translate('dbud.label.password.description'),
            'attributes' => array(
                'autocomplete' => 'off',
            ),
        ));

        if ($addUseKey) {
            $formBuilder->addRow('useKey', 'checkbox', array(
                'label' => $translator->translate('dbud.label.use'),
                'description' => $translator->translate('dbud.label.use.key.description'),
            ));
        }

        if ($addUsePassive) {
            $formBuilder->addRow('usePassive', 'checkbox', array(
                'label' => $translator->translate('dbud.label.use'),
                'description' => $translator->translate('dbud.label.use.passive.description'),
            ));
        }

        if ($addUseSsl) {
            $formBuilder->addRow('useSsl', 'checkbox', array(
                'label' => $translator->translate('dbud.label.use'),
                'description' => $translator->translate('dbud.label.use.ssl.description'),
            ));
        }
    }

}