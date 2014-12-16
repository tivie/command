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


namespace Tivie\Command\OS;
require_once(__DIR__ . '/../namespace.constants.php');


class OS
{
    public $name;

    public $family;

    public $def;

    public function __toString()
    {
        return $this->name;
    }

    public function isUnixLike()
    {
        return ($this->family === UNIX_FAMILY);
    }

    public function isWindows()
    {
        return ($this->family === WINDOWS_FAMILY);
    }
}