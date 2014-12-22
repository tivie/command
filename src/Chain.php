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
     * Runs the command chain
     *
     * @return Result[]
     */
    public function run()
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
