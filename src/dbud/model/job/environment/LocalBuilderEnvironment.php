<?php

namespace dbud\model\job\environment;

use zibo\library\filesystem\File;

/**
 * Interface for the script environment of the builder
 */
class LocalBuilderEnvironment extends AbstractBuilderEnvironment {

    /**
     * Runs the builder
     * @return string Log of the builder
     */
    public function runBuilder() {
        $directory = File::getTemporaryFile();
        $directory->delete();
        $directory->create();
        $directoryAbsolute = $directory->getAbsolutePath();

        $log = "# Created working directory " . $directoryAbsolute . "\n";

        $cwd = getcwd();
        $exception = null;

        try {
            chdir($directoryAbsolute);

            $variables = $this->getCommandVariables($directoryAbsolute);

            $commands = array();
            if ($this->builder->copyRepository) {
                $commands = array_merge($commands, $this->getCopyRepositoryCommands());
            }
            $commands = array_merge($commands, explode("\n", $this->builder->script));

            foreach ($commands as $command) {
                foreach ($variables as $variable => $value) {
                    $command = str_replace('%' . $variable . '%', $value, $command);
                }

                $log .= $command . "\n";

                if (substr($command, 0, 3) == 'cd ') {
                    chdir(substr($command, 3));

                    continue;
                }

                $log .= $this->executeCommand($command);
            }
        } catch (Exception $exception) {
            $this->setException($exception);
        }

        chdir($cwd);
        $directory->delete();

        $log .= "# Deleted working directory " . $directory->getAbsolutePath() . "\n";

        return $log;
    }

}