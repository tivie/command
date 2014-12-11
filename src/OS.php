<?php
/**
 * -- tivie-command -- 
 * DetectOs.php created at 11-12-2014
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

const RUN_REGARDLESS           = 0;
const RUN_IF_PREVIOUS_SUCCEEDS = 1;
const RUN_IF_PREVIOUS_FAILS    = 2;
const RUN_PIPE                 = 3;

const OS_NIX                   = 1;
const OS_WINDOWS               = 2;

class OS
{
    private static $os;

    /**
     * Symbol table for running modes
     *
     * @var array
     */
    public $symbols = array(
        RUN_REGARDLESS           => ';',
        RUN_IF_PREVIOUS_SUCCEEDS => '&&',
        RUN_IF_PREVIOUS_FAILS    => '||',
        RUN_PIPE                 => '|'
    );

    public function __construct()
    {
        $os = $this->detect();

        if ($os === OS_WINDOWS) {
            $this->runModes[RUN_REGARDLESS] = '&';
        }
    }

    public function detect()
    {
        if (is_null(self::$os)) {
            if (preg_match('/windows/i', php_uname('s'))) {
                self::$os = OS_WINDOWS;
            } else {
                self::$os = OS_NIX;
            }
        }
        return self::$os;
    }

    public function getSymbol($symbol)
    {
        return $this->symbols[$symbol];
    }
}