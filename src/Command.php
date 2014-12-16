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
use Tivie\Command\OS\OSDetector;
use Tivie\Command\OS\OSDetectorInterface;
use Traversable;

/**
 * Class Command
 * An utility class that provides a safer way to run system commands
 *
 * @package Tivie\Command
 */
class Command implements \IteratorAggregate
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
     * @var Argument[]
     */
    public $arguments = array();

    /**
     * @var mixed
     */
    protected $stdIn;

    /**
     * @var string
     */
    protected $tmpDir;

    /**
     * @var OSDetector
     */
    protected $os;

    /**
     * Create a new Command object
     *
     * @param int $flags
     * @param OSDetectorInterface $os
     * @throws InvalidArgumentException
     */
    public function __construct($flags = null, OSDetectorInterface $os = null)
    {
        if ($flags !== null) {
            if (!is_int($flags)) {
                throw new InvalidArgumentException('integer', 0);
            }
            $this->flags = $flags;
        }

        $this->os = ($os) ? $os : new OSDetector();

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
     * @param string|Argument $argument The argument name.
     * @param mixed $value [optional] The value(s) associated with the argument, if applicable
     * @param int $os [optional] If the argument should only be passed in a determined OS. Passing null means the
     * argument is passed in all environments. Default is null.
     * @param int $prepend [optional] If the argument should be prepended with dash, double-dash ou forward-slash
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addArgument($argument, $value = null, $os = null, $prepend = null)
    {
        if (!$argument instanceof Argument) {
            $escape = !($this->flags & DONT_ESCAPE);
            $argument = new Argument($argument, $value, $os, $escape, $prepend, $this->os);
        }

        $this->arguments[$argument->getIdentifier()] = $argument;

        return $this;
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
        if ($argument instanceof Argument) {
            $key = $argument->getIdentifier();

        } else if (is_string($argument)) {
            $key = preg_replace('#^--|^-|^/#', '', $argument);

        } else {
            throw new InvalidArgumentException('string or Argument', 0);
        }

        unset($this->arguments[$key]);

        return $this;
    }

    public function replaceArgument(Argument $oldArgument, Argument $newArgument)
    {
        $oldKey = $oldArgument->getIdentifier();
        $newKey = $newArgument->getIdentifier();
        $keys = array_keys($this->arguments);
        $index = array_search($oldKey, $keys);

        if ($index !== false) {
            $keys[$index] = $newKey;
            $array = array_combine($keys, $this->arguments);
            $array[$newKey] = $newArgument;
            $this->arguments = $array;
        }

        return $this;
    }

    /**
     * Gets the argument values
     *
     * @param string $argumentIdentifier The argument 'name'
     * @return Argument The argument
     * @throws InvalidArgumentException If $argument is not a string
     */
    public function getArgument($argumentIdentifier)
    {
        if (!is_string($argumentIdentifier)) {
            throw new InvalidArgumentException('string', 0);
        }

        if (!isset($this->arguments[$argumentIdentifier])) {
            return null;
        }

        return $this->arguments[$argumentIdentifier];

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
        $cmd = $this->command;

        foreach ($this->arguments as $argument) {
            $os = $argument->getOs();

            if ($os && !($os & $this->os->detect()->def)) {
                continue;
            }

            $key = $argument->getKey();
            $values = $argument->getValues();

            if (empty($values)) {
                $cmd .= " $key";
                continue;
            }

            foreach ($values as $val) {
                $cmd .= " $key";
                $cmd .= ($this->flags & DONT_ADD_SPACE_BEFORE_VALUE) ? '' : ' ';
                $cmd .= $val;
            }
        }
        return trim($cmd);
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

        if ($this->os->detect()->isWindows() && !($this->flags & FORCE_USE_PROC_OPEN)) {
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

            if ($this->os->detect()->isWindows()) {
                $cat = "type $filename";
            } else {
                $cat = "cat $filename";
            }

            $cmd = "$cat | $cmd";
        }

        $result->setLastLine(trim(exec("$cmd 2> $tempStdErr", $otp, $exitCode)));

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

            $lLine = explode(PHP_EOL, $result->getStdOut());
            $result->setLastLine(array_pop($lLine));
        }

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

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $array = $this->arguments;
        array_unshift($array, $this->getCommand());

        return new \ArrayIterator($array);
    }
}
