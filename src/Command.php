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

const FORCE_USE_PROC_OPEN         = 1;
const ESCAPE                      = 2;
const DONT_ADD_SPACE_BEFORE_VALUE = 4;
const FORCE_USE_EXEC              = 8;

const RUN_REGARDLESS              = 0;
const RUN_IF_PREVIOUS_SUCCEEDS    = 1;
const RUN_IF_PREVIOUS_FAILS       = 2;
const RUN_PIPED                   = 3;

const PIPE_PH = '!PIPE!';

use Tivie\Command\Exception\DomainException;
use Tivie\Command\Exception\Exception;
use Tivie\Command\Exception\InvalidArgumentException;
use Tivie\OS\Detector;
use Tivie\OS\DetectorInterface;
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
    protected $arguments = array();

    /**
     * @var mixed
     */
    protected $stdIn;

    /**
     * @var string
     */
    protected $tmpDir;

    /**
     * @var Detector
     */
    protected $os;

    /**
     * @var string
     */
    private $cwd;

    /**
     * Create a new Command object
     *
     * @param  int                      $flags
     * @param  DetectorInterface        $os
     * @throws InvalidArgumentException If $flags is not an integer
     * @throws DomainException If invalid flags are passed
     */
    public function __construct($flags = null, DetectorInterface $os = null)
    {
        if ($flags !== null) {
            $this->setFlags($flags);
        }

        $this->os = ($os) ? $os : new Detector();

        $this->tmpDir = sys_get_temp_dir();
    }

    /**
     * Set the flags for this Command
     *
     * @param integer $flags
     * @return $this
     * @throws InvalidArgumentException If $flags is not an integer
     * @throws DomainException
     */
    public function setFlags($flags)
    {
        if (!is_int($flags)) {
            throw new InvalidArgumentException('integer', 0);
        }

        if (($flags & FORCE_USE_PROC_OPEN) && ($flags & FORCE_USE_EXEC)) {
            throw new DomainException("Invalid flags: FORCE_USE_PROC_OPEN and FORCE_USE_EXEC cannot be set at the same time");
        }

        $this->flags = $flags;

        return $this;
    }

    /**
     * Get the current flags set for this Command
     *
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }
    
    /**
     * Sets the command to execute (without arguments)
     *
     * @param  string                   $command
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setCommand($command)
    {
        if (!is_string($command)) {
            throw new InvalidArgumentException('string', 0);
        }
        // escape command
        $this->command = ($this->flags & ESCAPE) ? escapeshellcmd($command) : $command;

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
     * @param  Argument $argument The argument name or an Argument Object.
     * @return $this
     */
    public function addArgument(Argument $argument)
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * Remove an Argument from Command
     *
     * @param  Argument                 $argument
     * @return $this
     * @throws InvalidArgumentException
     */
    public function removeArgument(Argument $argument)
    {
        $key = array_search($argument, $this->arguments, true);

        unset($this->arguments[$key]);

        return $this;
    }

    /**
     * Replace an Argument object with another Argument Object. The old Argument object must exist, or an exception will
     * be throw. You can check for argument existence with the method Command::argumentExists(). Also take note that
     * this method can be expensive since it iterates over the internal array.
     *
     * @param  Argument        $oldArgument
     * @param  Argument        $newArgument
     * @return $this
     * @throws DomainException Thrown if $oldArgument isn't set
     */
    public function replaceArgument(Argument $oldArgument, Argument $newArgument)
    {
        for ($i = 0; $i < count($this->arguments); ++$i) {
            if ($this->arguments[$i] === $oldArgument) {
                $this->arguments[$i] = $newArgument;

                return $this;
            }
        }
        throw new DomainException("oldArgument does not exist");
    }

    /**
     * Check if Argument exists in this Command object
     *
     * @param  Argument $argument
     * @return bool
     */
    public function argumentExists(Argument $argument)
    {
        return in_array($argument, $this->arguments, true);
    }

    /**
     * Get the argument specified by it's position($index)
     *
     * @param  int                      $index The argument position
     * @return Argument                 The argument or null if the $index is not set
     * @throws InvalidArgumentException If $index is not an integer
     */
    public function getArgument($index)
    {
        if (!is_int($index)) {
            throw new InvalidArgumentException('integer', 0);
        }

        if (!isset($this->arguments[$index])) {
            return null;
        }

        return $this->arguments[$index];
    }

    /**
     * Search for an Argument whose key or identifier matches $needle
     *
     * @param  mixed         $needle The search term
     * @return Argument|null Returns the first found argument or null if none is found.
     */
    public function searchArgument($needle)
    {
        foreach ($this->arguments as $arg) {
            if ($arg->getIdentifier() === $needle || $arg->getKey() === $needle) {
                return $arg;
            }
        }

        return null;
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
     * @param  mixed $stdIn
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

            if ($os && !($os & $this->os->getType())) {
                continue;
            }

            $key = $argument->getKey();
            $prefix = $argument->getPrefix();

            if ($argument->willEscape() === true || ($argument->willEscape() === null && ($this->flags & ESCAPE))) {
                $key = $this->escapeshellarg($key);
            }
            $key = $prefix.$key;

            $values = $argument->getValues();

            if (empty($values)) {
                $cmd .= " $key";
                continue;
            }

            foreach ($values as $val) {
                $cmd .= " $key";
                $cmd .= ($this->flags & DONT_ADD_SPACE_BEFORE_VALUE) ? '' : ' ';
                if ($argument->willEscape() === true || ($argument->willEscape() === null && ($this->flags & ESCAPE))) {
                    $val = $this->escapeshellarg($val);
                }
                $cmd .= $val;
            }
        }

        return trim($cmd);
    }

    /**
     * Runs the command and returns a result object
     *
     * @param  Result $result [optional] You can pass a result object to store the result of the run command.
     * If none is provided, one will be initialized automatically for you.
     * @return Result An object containing the result of the command
     */
    public function run(Result $result = null)
    {
        $cmd = $this->getBuiltCommand();

        $result = ($result) ? $result : new Result();
        if (($this->os->isWindowsLike() && !($this->flags & FORCE_USE_PROC_OPEN)) || ($this->flags & FORCE_USE_EXEC)) {
            return $this->exec($cmd, $result);
        } else {
            return $this->procOpen($cmd, $result);
        }
    }

    /**
     * Start the command chaining process. This command will become the first command in the chain
     *
     * @param  Chain $chain [optional] You can provide a pre initialized Chain object that will be used as "chainer".
     * If none is provided, one will be initialized automatically for you.
     * @return Chain Returns a Chain Object that you can use to add subsequent commands
     */
    public function chain(Chain $chain = null)
    {
        $chain = ($chain) ? $chain : new Chain();
        $chain->add($this);

        return $chain;
    }

    /**
     * Set the command's new working directory (alias to chdir)
     *
     * @param string $dir The new working directory
     * @param bool $check If $dir should be choked for existence
     * @return $this
     * @throws Exception If $dir does not exist or is not a directory
     * @throws InvalidArgumentException If $dir is not a string
     */
    public function setCurrentWorkingDirectory($dir, $check = true)
    {
        return $this->chdir($dir, $check);
    }

    /**
     * Set the command's new working directory
     *
     * @param string $dir The new working directory
     * @param bool $check If $dir should be choked for existence
     * @return $this
     * @throws Exception If $dir does not exist or is not a directory
     * @throws InvalidArgumentException If $dir is not a string
     */
    public function chdir($dir, $check = true)
    {
        if (!is_string($dir)) {
            throw new InvalidArgumentException("string", 0);
        }

        if ($check && !is_dir($dir)) {
            throw new Exception("No such directory $dir");
        }

        $this->cwd = $dir;

        return $this;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>
     */
    public function getIterator()
    {
        $array = $this->arguments;
        array_unshift($array, $this->getCommand());

        return new \ArrayIterator($array);
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
     * Method to run the command using exec
     *
     * @param  string    $cmd    The command string
     * @param  Result    $result A result object used to store the result of the command
     * @return Result    The command's result
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

            if ($this->os->isWindowsLike()) {
                $cat = "type $filename";
            } else {
                $cat = "cat $filename";
            }

            $cmd = "$cat | $cmd";
        }

        //Set CWD
        $prevCwd = getcwd();
        if ($this->cwd != null) {
            chdir($this->cwd);
        }

        $result->setLastLine(trim(exec("$cmd 2> $tempStdErr", $otp, $exitCode)));

        $result->setStdIn($this->stdIn)
            ->setStdOut(implode(PHP_EOL, $otp))
            ->setStdErr(file_get_contents($tempStdErr))
            ->setExitCode($exitCode);

        // restore CWD
        chdir($prevCwd);

        return $result;
    }

    /**
     * Method to run the command using proc_open
     *
     * @param  string $cmd    The command string
     * @param  Result $result A result object used to store the result of the command
     * @return Result The command's result
     */
    protected function procOpen($cmd, Result $result)
    {
        $spec = array(
            0 => array("pipe", "r"), // STDIN
            1 => array("pipe", "w"), // STDOUT
            2 => array("pipe", "w"),  // STDERR
        );

        $pipes = array();
        $exitCode = null;

        $process = proc_open($cmd, $spec, $pipes, $this->cwd);

        if (is_resource($process)) {
            $result->setStdIn($this->stdIn);

            if ($this->stdIn !== null) {
                fwrite($pipes[0], $this->stdIn);
            }
            fclose($pipes[0]);

            $result->setStdOut(trim(stream_get_contents($pipes[1])));
            fclose($pipes[1]);

            $result->setStdErr(trim(stream_get_contents($pipes[2])));
            fclose($pipes[2]);

            $result->setExitCode(proc_close($process));

            $lLine = explode(PHP_EOL, $result->getStdOut());
            $result->setLastLine(trim(array_pop($lLine)));
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

    private function escapeshellarg($arg)
    {
        //remove whitespaces
        $arg = trim($arg);

        // remove " from the beginning and end
        $arg = trim($arg, '"');

        if ($this->os->isWindowsLike()) {
            // escape " by doubling
            $arg = str_replace('"', '""', $arg);

            // check variable expansion
            $this->checkWindowsEnvVar($arg);

            $arg = "\"$arg\"";
        } else {
            $arg = escapeshellarg($arg);
        }

        return $arg;
    }

    private function checkWindowsEnvVar($argument)
    {
        if (preg_match_all('/%[^% ]+%/', $argument, $matches)) {
            foreach ($matches[0] as $arg) {
                $arg = trim($arg, '%');
                $env = getenv($arg);
                if ($env && strpos($env, '"') !== false) {
                    throw new Exception("The environment variable %$arg% value has doublequotes(\") and so it can't be automatically escaped!");
                }
            }
        }
    }
}
