<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 15-12-2014
 * Time: 15:56
 */

namespace Tivie\Command;

use Tivie\OS\Detector;

class RealTest extends \PHPUnit_Framework_TestCase
{
    public function testWithPingCommand()
    {
        $osDetect = new Detector();

        $cmd1 = new Command(ESCAPE);
        $cmd1->setCommand('ping')
            ->addArgument(new Argument('-n', 1, \Tivie\OS\WINDOWS_FAMILY))
            ->addArgument(new Argument('-l', 32, \Tivie\OS\WINDOWS_FAMILY))
            ->addArgument(new Argument('-c', 1, \Tivie\OS\UNIX_FAMILY))
            ->addArgument(new Argument('-s', 24, \Tivie\OS\UNIX_FAMILY))
            ->addArgument(new Argument('www.google.com'));

        if ($osDetect->isWindowsLike()) {
            $cmdStr = 'ping -"n" "1" -"l" "32" "www.google.com"';
        } else {
            $cmdStr = "ping -'c' '1' -'s' '24' 'www.google.com'";
        }

        self::assertEquals($cmdStr, $cmd1->getBuiltCommand());

        $cmd2 = new Command();
        $cmd2->setCommand('php')
            ->addArgument(new Argument(__DIR__."/cmd.php"))
            ->addArgument(new Argument('--hasStdIn'));

        $results = $cmd1->chain()
            ->add($cmd2, RUN_REGARDLESS, true)
            ->run();

        $res = json_decode($results[1]->getStdOut());
        self::assertEquals(2, $res->NumOfArgs);
        self::assertEquals(trim($results[0]->getStdOut()), $results[1]->getStdIn());
    }

    public function testPipeAsArgument()
    {
        $otp = 'foobar';

        $cmd1 = new Command();
        $cmd1->setCommand('php')
            ->addArgument(new Argument(__DIR__."/cmd.php"))
            ->addArgument(new Argument('--otp', $otp));

        $cmd2 = new Command();
        $cmd2->setCommand('php')
            ->addArgument(new Argument(__DIR__."/cmd.php"))
            ->addArgument(new Argument('--otp', PIPE_PH));

        $result = $cmd1->chain()->add($cmd2, null, true)->run();

        self::assertEquals($otp, $result[1]->getStdOut());
    }

    public function testPipeAsArgument2()
    {
        $otp = 'foobar';

        $cmd1 = new Command();
        $cmd1->setCommand('php')
            ->addArgument(new Argument(__DIR__."/cmd.php"))
            ->addArgument(new Argument('--otp', $otp));

        $cmd2 = new Command();
        $cmd2->setCommand('php')
            ->addArgument(new Argument(__DIR__."/cmd.php"))
            ->addArgument(new Argument(PIPE_PH));

        $result = $cmd1->chain()->add($cmd2, null, true)->run();

        $res = json_decode($result[1]->getStdOut());

        self::assertEquals($otp, $res->Args[1]);
    }
}
