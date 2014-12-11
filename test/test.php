<?php
/**
 * -- tivie-command -- 
 * test.php created at 10-12-2014
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

require __DIR__ . '/../vendor/autoload.php';

$cmd = new \Tivie\Command\Command();

$cmd->setCommand('php testCmd.php')
    ->addArgument('foo')
    ->addArgument('bar', 'barVal')
    ->setStdIn('my stdin');

$cmd1 = new \Tivie\Command\Command();
$cmd1->setCommand('echo')->addArgument('foo');

$cmd2 = new \Tivie\Command\Command();
$cmd2->setCommand('php testCmd.php');

$cmd3 = new \Tivie\Command\Command();
$cmd3->setCommand('mkdir');

$cmd4 = new \Tivie\Command\Command();
$cmd4->setCommand('dir');

$results = $cmd1->chain()->add($cmd2, \Tivie\Command\RUN_PIPE)->add($cmd3, \Tivie\Command\RUN_IF_PREVIOUS_SUCCEEDS)->run();

var_dump($results);