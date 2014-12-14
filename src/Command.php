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

require_once(__DIR__ . '/namespace.constants.php');

use Tivie\Command\Exception\Exception;
use Tivie\Command\Exception\InvalidArgumentException;

/**
 * Class Command
 * An utility class that provides a safer way to run system commands
 *
 * @package Tivie\Command
 */
class Command
{
    /**
     * @var int
     */
    public $_runMode = 0;

    /**
     * @var bool
     */
    public $_pipe = false;

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
     * @var array
     */
    protected $unparsedArguments = array();

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

    /**
     * Create a new Command object
     *
     * @param int $flags
     * @param OS $os
     * @throws InvalidArgumentException
     */
    public function __construct($flags = null, OS $os = null)
    {
        if ($flags !== null) {
            if (!is_int($flags)) {
                throw new InvalidArgumentException('integer', 0);
            }
            $this->flags = $flags;
        }

        $this->os = ($os) ? $os : new OS();

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
     * Adds an argument to the command
     *
     * @param string $argument The argument name.
     * @param mixed $value [optional] The value(s) associated with the argument, if applicable
     * @param int $prepend [optional] If the argument should be prepended with dash, double-dash ou forward-slash
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addArgument($argument, $value = null, $prepend = null)
    {
        if (!is_string($argument)) {
            throw new InvalidArgumentException('string', 0);
        }

        if ($argument == null) {
            throw new InvalidArgumentException('string', 0, 'Cannot be null');
        }

        $prepArgument = $this->prepareArgumentKey($argument, $prepend);

        $fValues = array();

        if (!is_array($value)) {
            $value = array($value);
        }

        foreach ($value as $val) {
            if (!is_null($val)) {
                $fValues[] = ($this->flags & DONT_ESCAPE) ? $val : escapeshellarg($val);
            }
            $this->arguments[$prepArgument] = $fValues;
            $this->unparsedArguments[$argument] = $prepArgument;
        }

        return $this;
    }

    private function prepareArgumentKey($argument, $prepend)
    {
        $argument = ($this->flags & DONT_ESCAPE) ? $argument : escapeshellcmd($argument);


        if ($prepend === PREPEND_OS_DETECTION) {
            switch ($this->os->detect()) {
                case OS_WINDOWS:
                    $prepend = PREPEND_WINDOWS_STYLE;
                    break;
                case OS_NIX:
                    $prepend = PREPEND_UNIX_STYLE;
                    break;
            }
        }

        switch ($prepend){
            case PREPEND_UNIX_STYLE:
                $argument = (strlen($argument) === 1) ? "-$argument" : "--$argument";
                break;

            case PREPEND_WINDOWS_STYLE:
                $argument = "/$argument";
                break;
        }
        return $argument;
    }

    /**
     * Remove an argument from command
     *
     * @param $argument
     * @return $this
     * @throws InvalidArgumentException
     */
    public function removeArgument($argument)
    {
        if (!is_string($argument)) {
            throw new InvalidArgumentException('string', 0);
        }

        // Passed an unparsed argument
        if (isset($this->unparsedArguments[$argument])) {
            $key = $this->unparsedArguments[$argument];
            unset($this->unparsedArguments[$argument]);
            unset($this->arguments[$key]);
        }

        //passed a parsed argument
        if (in_array($argument, $this->unparsedArguments)) {
            $keys = array_keys($this->unparsedArguments, $argument);
            foreach ($keys as $k) {
                unset($this->unparsedArguments[$k]);
            }
        }

        unset($this->arguments[$argument]);

        return $this;
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

        if (isset($this->unparsedArguments[$argument])) {
            $argument = $this->unparsedArguments[$argument];
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
     * Get the built command
     *
     * @return string
     */
    public function getBuiltCommand()
    {
        if ($this->builtCommand === null) {
            $this->build();
        }
        return $this->builtCommand;
    }

    /**
     * Returns a string representation of the command
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getBuiltCommand();
    }

    /**
     * Runs the command and returns a result object
     *
     * @param Result $result [optional] You can pass a result object to store the result of the runned command
     * @return Result An object containing the result of the command
     */
    public function run(Result $result = null)
    {
        $cmd = $this->getBuiltCommand();

        $result = ($result) ? $result : new Result();

        if ($this->os->detect() === OS_WINDOWS && !($this->flags & FORCE_USE_PROC_OPEN)) {
            return $this->exec($cmd, $result);
        } else {
            return $this->procOpen($cmd, $result);
        }
    }

    /**
     *
     * @param Chain $chain
     * @return Chain
     */
    public function chain(Chain $chain = null)
    {
        $chain = ($chain) ? $chain : new Chain();
        $chain->add($this);
        return $chain;
    }

    /**
     * Method to run the command using exec
     *
     * @param string $cmd The command string
     * @param Result $result A result object used to store the result of the command
     * @return Result The command's result
     * @throws Exception
     */
    protected function exec($cmd, Result $result)
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

    /**
     * Method to run the command using proc_open
     *
     * @param string $cmd The command string
     * @param Result $result A result object used to store the result of the command
     * @return Result The command's result
     */
    protected function procOpen($cmd, Result $result)
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

            $result->setStdIn($this->stdIn);

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

    /**
     * Method used to build the command with arguments
     *
     * @return $this
     */
    protected function build()
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

    private function createFauxStdIn($stdIn)
    {
        $tempFile = tempnam($this->tmpDir, 'cmr');

        if (!file_put_contents($tempFile, $stdIn)) {
            throw new Exception("Error creating temporary file $tempFile");
        }

        return $tempFile;
    }
}
