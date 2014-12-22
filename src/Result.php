<?php
/**
 * -- tivie-command --
 * Result.php created at 10-12-2014
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

class Result
{
    /**
     * @var string
     */
    protected $stdIn;

    /**
     * @var string
     */
    protected $stdOut;

    /**
     * @var string
     */
    protected $stdErr;

    /**
     * @var int
     */
    protected $exitCode;

    /**
     * @var string
     */
    protected $lastLine;

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @param  int   $exitCode
     * @return $this
     */
    public function setExitCode($exitCode)
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getStdErr()
    {
        return $this->stdErr;
    }

    /**
     * @param string $stdErr
     * @return $this
     */
    public function setStdErr($stdErr)
    {
        $this->stdErr = $stdErr;

        return $this;
    }

    /**
     * @return string
     */
    public function getStdIn()
    {
        return $this->stdIn;
    }

    /**
     * @param string $stdIn
     * @return $this
     */
    public function setStdIn($stdIn)
    {
        $this->stdIn = $stdIn;

        return $this;
    }

    /**
     * @return string
     */
    public function getStdOut()
    {
        return $this->stdOut;
    }

    /**
     * @param string $stdOut
     * @return $this
     */
    public function setStdOut($stdOut)
    {
        $this->stdOut = $stdOut;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastLine()
    {
        return $this->lastLine;
    }

    /**
     * @param  string $lastLine
     * @return $this
     */
    public function setLastLine($lastLine)
    {
        $this->lastLine = $lastLine;

        return $this;
    }
}
