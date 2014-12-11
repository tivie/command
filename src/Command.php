<?php
/**
 * -- tivie-command --
 * Command.php created at 10-12-2014
 * 
 * Copyright 2014 Estevão Soares dos Santos
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/


namespace Tivie\Command;

use Tivie\Command\Exception\Exception;
use Tivie\Command\Exception\InvalidArgumentException;

//FLAGS
const FORCE_USE_PROC_OPEN         = 1;
const DONT_ESCAPE                 = 2;
const DONT_ADD_SPACE_BEFORE_VALUE = 4;

class Command
{
    /**
     * @var int
     */
    public $_runMode = 0;

    /**
     * @var int
     */
    protected $flags = 0;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var array[]
     */
    protected $arguments = array();

    /**
     * @var string
     */
    protected $builtCommand = null;

    /**
     * @var mixed
     */
    protected $stdIn;

    /**
     * @var string
     */
    protected $tmpDir;

    /**
     * @var OS
     */
    protected $os;

    public function __construct($flags = null)
    {
        if ($flags !== null) {
            if (!is_int($flags)) {
                throw new InvalidArgumentException('integer', 0);
            }
            $this->flags = $flags;
        }

        $this->os = new OS();

        $this->tmpDir = sys_get_temp_dir();
    }

    /**
     * Sets the command to execute (without arguments)
     *
     * @param string $command
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setCommand($command)
    {
        if (!is_string($command)) {
            throw new InvalidArgumentException('string', 0);
        }
        // escape command
        $this->command = ($this->flags & DONT_ESCAPE) ? $command : escapeshellcmd($command);

        return $this;
    }

    /**
     * Gets the base command to execute (without arguments)
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $argument
     * @param mixed $value [optional] The value associated with the argument, if applicable
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addArgument($argument, $value = null)
    {
        if (!is_string($argument)) {
            throw new InvalidArgumentException('string', 0);
        }

        $argument = ($this->flags & DONT_ESCAPE) ? $argument : escapeshellcmd($argument);

        $fValues = array();

        if (!is_array($value)) {
            $value = array($value);
        }

        foreach ($value as $val) {
            if (!is_null($val)) {
                $fValues[] = ($this->flags & DONT_ESCAPE) ? $val : escapeshellarg($val);
            }
            $this->arguments[$argument] = $fValues;
        }

        return $this;
    }

    public function removeArgument($argument)
    {
        if (!is_string($argument)) {
            throw new InvalidArgumentException('string', 0);
        }
        unset($this->arguments[$argument]);
    }

    /**
     * Gets the argument values
     *
     * @param string $argument The argument 'name'
     * @return array|null The argument values array or null if not found
     * @throws Exception If argument is not set
     * @throws InvalidArgumentException If $argument is not a string
     */
    public function getArgumentValue($argument)
    {
        if (!is_string($argument)) {
            throw new InvalidArgumentException('string', 0);
        }

        if (isset($this->arguments[$argument])) {
            if (count($this->arguments[$argument]) > 1) {
                return $this->arguments[$argument];
            } else if (count($this->arguments[$argument]) === 1) {
                return $this->arguments[$argument][0];
            } else {
                return null;
            }
        }
        throw new Exception("Argument $argument isn't set");
    }

    /**
     * Sets the arguments to be passed to the command
     *
     * @param array $arguments The arguments to be passed with the command
     * @return $this
     * @throws InvalidArgumentException If $arguments is not an array of strings
     */
    public function setArguments(array $arguments = array())
    {
        foreach ($arguments as $key=>$val) {
            $this->addArgument($key, $val);
        }

        return $this;
    }

    /**
     * Gets the array of arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Sets the Standard Input for command
     *
     * @param mixed $stdIn
     * @return $this
     */
    public function setStdIn($stdIn)
    {
        $this->stdIn = $stdIn;

        return $this;
    }

    /**
     * Gets the Standard Input of command
     *
     * @return mixed
     */
    public function getStdIn()
    {
        return $this->stdIn;
    }

    /**
     * @return $this
     */
    public function build()
    {
        $cmd = $this->command;

        foreach ($this->arguments as $arg => $valArray) {
            if (empty($valArray)) {
                $cmd .= " $arg";
                continue;
            }

            foreach ($valArray as $val) {
                $cmd .= " $arg";
                $cmd .= ($this->flags & DONT_ADD_SPACE_BEFORE_VALUE) ? '' : ' ';
                $cmd .= $val;
            }
        }
        $this->builtCommand = $cmd;

        return $this;
    }

    /**
     * @return string
     */
    public function getBuiltCommand()
    {
        if ($this->builtCommand === null) {
            $this->build();
        }
        return $this->builtCommand;
    }

    public function __toString()
    {
        return $this->getBuiltCommand();
    }


    public function run()
    {
        $cmd = $this->getBuiltCommand();

        $result = new Result();
        if ($this->os->detect() === OS_WINDOWS && !($this->flags & FORCE_USE_PROC_OPEN)) {
            return $this->exec($cmd, $result);
        } else {
            return $this->procOpen($cmd, $result);
        }
    }

    public function chain()
    {

    }

    private function exec($cmd, Result $result)
    {
        //Tmp file to store stderr
        $tempStdErr = tempnam($this->tmpDir, 'cmr');

        if (!$tempStdErr) {
            throw new Exception("Could not create temporary file on $tempStdErr");
        }

        $otp = array();
        $exitCode = null;

        if ($this->stdIn !== null) {
            $filename = $this->createFauxStdIn($this->stdIn);

            if ($this->os->detect() === OS_WINDOWS) {
                $cat = "type $filename";
            } else {
                $cat = "cat $filename";
            }

            $cmd = "$cat | $cmd";
        }

        exec("$cmd 2> $tempStdErr", $otp, $exitCode);

        $result->setStdIn($this->stdIn)
               ->setStdOut(implode(PHP_EOL, $otp))
               ->setStdErr(file_get_contents($tempStdErr))
               ->setExitCode($exitCode);

        return $result;
    }

    private function createFauxStdIn($stdIn)
    {
        $tempFile = tempnam($this->tmpDir, 'cmr');

        if (!file_put_contents($tempFile, $stdIn)) {
            throw new Exception("Error creating temporary file $tempFile");
        }

        return $tempFile;
    }

    private function procOpen($cmd, Result $result)
    {
        $spec = array(
            0 => array("pipe", "r"), // STDIN
            1 => array("pipe", "w"), // STDOUT
            2 => array("pipe", "w")  // STDERR
        );

        $pipes = array();
        $exitCode = null;

        $process = proc_open($cmd, $spec, $pipes);

        if (is_resource($process)) {

            if ($this->stdIn !== null) {
                fwrite($pipes[0], $this->stdIn);
            }
            fclose($pipes[0]);

            $result->setStdOut(stream_get_contents($pipes[1]));
            fclose($pipes[1]);

            $result->setStdErr(stream_get_contents($pipes[2]));
            fclose($pipes[2]);

            $result->setExitCode(proc_close($process));
        }

        return $result;
    }
}