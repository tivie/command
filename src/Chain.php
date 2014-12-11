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


class Chain
{
    /**
     * @var Command[]
     */
    protected $commands = array();

    protected $osDetector;

    public function __construct()
    {
        $this->os = new OS();

    }

    public function add(Command $cmd, $mode = RUN_REGARDLESS)
    {
        $cmd->_runMode = $mode;
        $this->commands[] = $cmd;

        return $this;
    }

    public function run()
    {
        $result = new Result();
        $resultArray = array();

        for ($i=0; $i<count($this->commands); ++$i) {

            $cmd = $this->commands[$i];

            if ($i===0) {
                $resultArray[] = $result = $cmd->run();
                continue;
            }

            switch ($cmd->_runMode) {

                case RUN_IF_PREVIOUS_SUCCEEDS:
                    if ($result->getExitCode() !== 0) {
                        return $resultArray;
                    }
                    break;

                case RUN_IF_PREVIOUS_FAILS:
                    if ($result->getExitCode() === 0) {
                        return $resultArray;
                    }
                    break;

                case RUN_PIPE:
                    $resultArray[] = $result = $cmd->setStdIn($result->getStdIn());
                    break;
            }
            $resultArray[] = $result = $cmd->run();
        }
        return $resultArray;
    }
}