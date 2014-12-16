<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 14-12-2014
 * Time: 03:14
 */

namespace Tivie\Command
{
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

    const PIPE_PH                     = '!PIPE!';
}

namespace Tivie\Command\OS
{
    const UNIX_FAMILY                  = 1;
    const WINDOWS_FAMILY               = 2;
    const OTHER_FAMILY                 = 4;

    const WINDOWS                      = 10;   //    8 + 2
    const GEN_UNIX                     = 17;   //   16 + 2
    const MACOSX                       = 33;   //   32 + 1
    const LINUX                        = 65;   //   64 + 1
    const MSYS                         = 129;  //  128 + 1
    const CYGWIN_NT                    = 257;  //  256 + 1
    const SUN_OS                       = 513;  //  512 + 1
    const AIX                          = 1025; // 1024 + 1
    const QNX                          = 2049; // 2048 + 1
    const BSD                          = 4097; // 4096 + 1
    const BE_OS                        = 8195; // 8192 + 4
}
