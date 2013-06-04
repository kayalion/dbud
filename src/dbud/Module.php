<?php

namespace dbud;

use zibo\app\model\MenuItem;
use zibo\app\model\Taskbar;

use zibo\core\Zibo;

use zibo\library\filesystem\File;

/**
 * Module for the Deploy-Buddy
 */
class Module {

    /**
     * Add the menu item to the taskbar
     * @param zibo\core\Zibo $zibo Instance of zibo
     * @param zibo\app\model\Taskbar $taskbar Instance of the taskbar
     * @return null
     */
    public function prepareTaskbar(Zibo $zibo, Taskbar $taskbar) {
        $menuItem = new MenuItem();
        $menuItem->setTranslation('dbud.title.project.overview');
        $menuItem->setRoute('dbud.project.overview');

        $applicationsMenu = $taskbar->getApplicationsMenu();
        $applicationsMenu->addMenuItem($menuItem);
    }

    /**
     * Gets the public key of the system
     * @param zibo\core\Zibo $zibo
     * @return boolean|string
     */
    public function getPublicKey(Zibo $zibo) {
        $publicKeyFile = $zibo->getParameter('dbud.ssh.key.public');
        if (!$publicKeyFile) {
            return false;
        }

        $publicKeyFile = new File($publicKeyFile);
        if (!$publicKeyFile->exists()) {
            return false;
        }

        return $publicKeyFile->read();
    }

}