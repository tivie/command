<?php
/**
 * -- tivie-command --
 * Chainer.php created at 11-12-2014
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
use Tivie\OS\Detector;
use Tivie\OS\DetectorInterface;

/**
 * Class Chain
 * A Helper class that controls command chaining
 *
 * @package Tivie\Command
 */
class Chain
{
    /**
     * @var Command[]
     */
    protected $commands = array();

    /**
     * @var bool
     */
    protected $buildFirst = false;

    /**
     * @var DetectorInterface
     */
    protected $os;

    public function __construct(DetectorInterface $os = null)
    {
        $this->os = ($os) ? $os : new Detector();
    }


    /**
     * Adds a new command to the chain
     *
     * @param  Command $cmd  The command object to add
     * @param  int     $mode [optional] one of the following constants:
     *                       RUN_IF_PREVIOUS_SUCCEEDS Only run if the previous command is successful (returns exitcode 0)
     *                       RUN_IF_PREVIOUS_FAILS Only run if the previous command fails (returns exitcode != 0)
     *                       RUN_REGARDLESS - Default. Runs regardless of previous command exit code
     * @param  bool    $pipe [optional] If the output of the previous command should be piped to this one.
     *                       If set to true, it will look for an argument whose value is !!PIPE!! and replace it
     *                       with the previous command STDOUT. If none is found, it will pass the previous command
     *                       STDOUT as this command STDIN.
     *                       Default is false.
     * @return $this
     */
    public function add(Command $cmd, $mode = RUN_REGARDLESS, $pipe = false)
    {
        $cmd->_runMode = $mode;
        $cmd->_pipe = !!$pipe;
        $this->commands[] = $cmd;

        return $this;
    }

    /**
     * If called (with true), a command chain string will be constructed first, with command chaining operators and then run as
     * a single command
     *
     * @param bool $bool [optional] Default is true
     * @return $this
     */
    public function buildFirst($bool = true)
    {
        $this->buildFirst = !!$bool;

        return $this;
    }

    /**
     * Runs the command chain
     *
     * @return Result[]
     */
    public function run()
    {
        if (count($this->commands) === 0) {
            return '';
        }
        if ($this->buildFirst()) {
            return $this->runAsSingleCommand();
        } else {
            return $this->runAsCommandSequence();
        }
    }

    //find . -name “*.java” | xargs accurev keep -c “comment”
    //FOR /F %k in (‘dir /s /b *.java’) DO accurev keep -c “comment” %k
    private function runAsSingleCommand()
    {
        $cmdStr = $this->commands[0]->getBuiltCommand();

        for ($i = 1; $i < count($this->commands); ++$i) {
            $cmd = $this->commands[$i];

            if ($cmd->_runMode === RUN_IF_PREVIOUS_SUCCEEDS) {
                $cmdStr .= $cmd->getSymbol(RUN_IF_PREVIOUS_SUCCEEDS);

            } else if ($cmd->_runMode === RUN_IF_PREVIOUS_FAILS) {
                $cmdStr .= $cmd->getSymbol(RUN_IF_PREVIOUS_FAILS);

            } else if ($cmd->_runMode === RUN_PIPED) {
                $cmdStr .= $cmd->getSymbol(RUN_PIPED);

            } else if ($cmd->_runMode === RUN_PIPE_AS_ARG) {
                $this->parseXArgs($cmd, '%P');

                if ($this->os->isWindowsLike()) {
                    $cmdStr .= "FOR /F %P IN ('";
                    $cmdStr .= $cmd->getBuiltCommand();
                    $cmdStr .= "') DO ";

                } else if ($this->os->isUnixLike()) {
                    $this->parseXArgs($cmd, '%P');

                } else {
                    throw new Exception("Unsupported OS for mode RUN_PIPE_AS_ARG");
                }


            }

            if ($cmd->_pipe) {






            }

            $cmdStr .= $cmd->getBuiltCommand();
        }

    }

    private function parseXArgs(Command $cmd, $replacement)
    {
        $isXArg = false;

        foreach ($cmd as $key => $arg) {

            if ($arg instanceof Argument) {

                // Replace !PIPE! in key
                if (stripos($key, PIPE_PH) !== false) {
                    $key = str_replace(PIPE_PH, $replacement, $key);
                    $arg->setKey($key);
                    $isXArg = true;
                }

                // Replace !PIPE! in args
                $values = $arg->getValues();
                foreach ($values as $index => $val) {

                    if (stripos($val, PIPE_PH) !== false) {
                        $val = str_replace(PIPE_PH, $replacement, $val);
                        $arg->replaceValue($index, $val);
                        $isXArg = true;
                    }
                }
            }
        }

        return $isXArg;
    }

    private function runAsCommandSequence()
    {
        //Bogus variable set. The original value is never used, but IDEs complain
        $result = new Result();
        $resultArray = array();

        for ($i = 0; $i < count($this->commands); ++$i) {
            $cmd = $this->commands[$i];

            if ($i === 0) {
                $resultArray[] = $result = $cmd->run();
                continue;
            }

            if (($cmd->_runMode === RUN_IF_PREVIOUS_SUCCEEDS && $result->getExitCode() !== 0) ||
                ($cmd->_runMode === RUN_IF_PREVIOUS_FAILS && $result->getExitCode() === 0)
            ) {
                continue;
            }

            if ($cmd->_pipe) {
                $stdOut = trim($result->getStdOut());

                $isXArg = false;
                foreach ($cmd as $argIdx => $arg) {
                    if ($arg instanceof Argument) {
                        if (stripos($arg->getKey(true), PIPE_PH) !== false) {
                            $key = str_replace(PIPE_PH, $stdOut, $arg->getKey(true));
                            $arg->setKey($key);
                        }

                        $values = $arg->getValues();
                        foreach ($values as $index => $val) {
                            if (stripos($val, PIPE_PH) !== false) {
                                $val = str_replace(PIPE_PH, $stdOut, $val);
                                $arg->replaceValue($index, $val);
                                $isXArg = true;
                            }
                        }
                    }
                }
                if (!$isXArg) {
                    $cmd->setStdIn($stdOut);
                }
            }

            $resultArray[] = $result = $cmd->run();
        }

        return $resultArray;
    }

}
