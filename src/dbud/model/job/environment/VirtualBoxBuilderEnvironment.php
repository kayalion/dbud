<?php

namespace dbud\model\job\environment;

use zibo\library\filesystem\File;
use zibo\library\String;

use \Exception;

/**
 * Queue job to run a builder script
 */
class VirtualBoxBuilderEnvironment extends AbstractBuilderEnvironment {

    /**
     * Name of the virtual machine
     * @var string
     */
    protected $name;

    /**
     * Time in seconds to wait for the virtual machine to boot
     * @var integer
     */
    protected $bootTime;

    /**
     * Username for the user in the virtual machine
     * @var string
     */
    protected $username;

    /**
     * Password for the user in the virtual machine
     * @var string
     */
    protected $password;

    /**
     * Flag to see if the virtual machine should be cloned before running the
     * builder
     * @var boolean
     */
    protected $cloneMachine;

    /**
     * Sets the name of the virtual machine
     * @param string $name
     * @return null
     */
    public function setVirtualMachineName($name) {
        $this->name = $name;
    }

    /**
     * Sets the boot time of the virtual machine
     * @param integer $bootTime Boot time in seconds
     * @return null
     */
    public function setVirtualMachineBootTime($bootTime) {
        $this->bootTime = $bootTime;
    }

    /**
     * Sets the username for the user of the virtual machine
     * @param string $username
     * @return null
     */
    public function setVirtualMachineUsername($username) {
        $this->username = $username;
    }

    /**
     * Sets the password for the user of the virtual machine
     * @param string $password
     * @return null
     */
    public function setVirtualMachinePassword($password) {
        $this->password = $password;
    }

    /**
     * Sets whether the virtual machine should be cloned
     * @param boolean $clone
     * @return null
     */
    public function setCloneVirtualMachine($clone) {
        $this->cloneMachine = $clone;
    }

    /**
     * Runs the builder
     * @return string Log of the builder
     */
    public function runBuilder() {
        if (!$this->name) {
            throw new Exception('Could not run the builder environment: no virtual machine name set');
        }

        if (!$this->bootTime) {
            throw new Exception('Could not run the builder environment: no virtual machine boot time set');
        }

        if (!$this->username) {
            throw new Exception('Could not run the builder environment: no virtual machine username set');
        }

        if (!$this->password) {
            throw new Exception('Could not run the builder environment: no virtual machine passwordset');
        }

        $log = '';

        try {
            if ($this->cloneMachine) {
                $machine = $this->name . '-' . String::generate();

                $directory = '/tmp/' . $machine;
            } else {
                $machine = $this->name;

                $directory = '/tmp/' . String::generate();
            }

            $variables = $this->getCommandVariables($directory);

            $commands = array(
                "export HOME=/home/" . $this->username,
                "export SHELL=/bin/bash",
                "export DISPLAY=:0.0",
                "export PATH=\$PATH:/usr/local/bin",
                "source ~/.phpbrew/bashrc",
                'mkdir ' . $directory,
                'cd ' . $directory,
            );

            if ($this->builder->copyRepository) {
                $commands = array_merge($commands, $this->getCopyRepositoryCommands());
            }

            $commands = array_merge($commands, explode("\n", $this->builder->script));

            if ($this->cloneMachine) {
                $commands[] = 'cd /';
                $commands[] = 'rm -r ' . $directory;
            }

            $script = "#/bin/sh\n\n";
            foreach ($commands as $index => $command) {
                foreach ($variables as $variable => $value) {
                    $command = str_replace('%' . $variable . '%', $value, $command);
                }

                $script .= "echo \"# builder command: " . $command . "\"\n" . $command;
                if (substr($command, -1) != '&') {
                    $script .= " || exit $?";
                }
                $script .= "\n";
            }

            $file = File::getTemporaryFile();
            $file->write($script);

            if ($this->cloneMachine) {
                $log .= "# Creating virtual machine\n";

                $command = 'VBoxManage clonevm ' . $this->name . ' --mode all --options keepallmacs --name ' . $machine . ' --register';
                $log .= $this->executeCommand($command);
            }

            $log .= "# Starting virtual machine\n";

            $command = 'virtualbox --startvm ' . $machine . ' > /dev/null &';
            $log .= $this->executeCommand($command);
            $this->executeCommand('sleep ' . $this->bootTime);

            $log .= "# Copy builder script to virtual machine\n";
            $command = 'VBoxManage guestcontrol ' . $machine . ' copyto ' . $file . ' /home/' . $this->username . '/builder.sh --username ' . $this->username . ' --password ' . $this->password;
            $log .= $this->executeCommand($command);

            $log .= "# Running builder script\n";
            $command = 'VBoxManage guestcontrol ' . $machine . ' exec --image "/bin/bash" --username ' . $this->username . ' --password ' . $this->password . ' --verbose --wait-exit --wait-stdout --wait-stderr -- /home/' . $this->username . '/builder.sh > ' . $file . '.out 2> ' . $file . '.err';
            try {
                $this->executeCommand($command) . "\n";

                $isError = false;
            } catch (Exception $e) {
                if ($this->log) {
                    $this->log->logException($e);
                }

                $isError = true;
            }

            $outputFile = new File($file->getPath() . '.out');
            $errorFile = new File($file->getPath() . '.err');

            $output = trim($outputFile->read());
            $error = trim($errorFile->read());
            $exitCode = 0;
            $lastCommand = null;

            if ($output) {
                $output = explode("\n", $output);
                foreach ($output as $line) {
                    if (strpos($line, 'Exit code=') === 0) {
                        $line = substr($line, 10);

                        list($exitCode, $null) = explode(' ', $line);

                        continue;
                    }

                    if (strpos($line, '# builder command: ') === 0) {
                        $lastCommand = substr($line, 19);

                        $log .= $lastCommand . "\n";

                        continue;
                    }

                    $log .= '# | ' . $line . "\n";
                }
            }

            if ($error) {
                if (strpos($error, ' :') !== false) {
                    list($errorCommand, $null, $error) = explode(' :', $error, 3);
                }

                $log .= '# | Error: ' . $error . "\n";
            } elseif ($exitCode != 0) {
                $error = $lastCommand . ' returned exit code ' . $exitCode;
                $log .= '# | Error: ' . $error . "\n";
            }

            $outputFile->delete();
            $errorFile->delete();
            $file->delete();

            $log .= "# Shutdown virtual machine\n";
            $command = 'VBoxManage controlvm ' . $machine . ' poweroff';
            $this->executeCommand($command);

            if ($this->cloneMachine) {
                $log .= "# Delete virtual machine\n";
                $command = 'VBoxManage unregistervm ' . $machine . ' --delete';
                $this->executeCommand($command);
            }

            if ($error) {
                throw new Exception($error);
            }
        } catch (Exception $exception) {
            $this->setException($exception);
        }

        return $log;
    }

}