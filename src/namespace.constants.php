<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 14-12-2014
 * Time: 03:14
 */

namespace Tivie\Command;

//COMMAND FLAGS
const FORCE_USE_PROC_OPEN         = 1;
const DONT_ESCAPE                 = 2;
const DONT_ADD_SPACE_BEFORE_VALUE = 4;

const PREPEND_UNIX_STYLE          = 1;
const PREPEND_WINDOWS_STYLE       = 2;
const PREPEND_OS_DETECTION        = 3;

const RUN_REGARDLESS              = 0;
const RUN_IF_PREVIOUS_SUCCEEDS    = 1;
const RUN_IF_PREVIOUS_FAILS       = 2;
const PIPE                        = 4;

const OS_NIX                      = 1;
const OS_WINDOWS                  = 2;