<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 15-12-2014
 * Time: 15:56
 */

namespace Tivie\Command;


use Tivie\Command\OS\OSDetector;

class RealTest extends \PHPUnit_Framework_TestCase
{

    public function testWithPingCommand()
    {
        $cmd1 = new Command();
        $cmd1->setCommand('ping')
            ->addArgument('-n', 1, \Tivie\Command\OS\WINDOWS_FAMILY)
            ->addArgument('-l', 32, \Tivie\Command\OS\WINDOWS_FAMILY)
            ->addArgument('-c', 1, \Tivie\Command\OS\UNIX_FAMILY)
            ->addArgument('-s', 24, \Tivie\Command\OS\UNIX_FAMILY)
            ->addArgument('www.google.com');

        $osDetect = new OSDetector();

        if ($osDetect->detect()->isWindows()) {
            $cmdStr = 'ping -"n" "1" -"l" "32" "www.google.com"';
        } else {
            $cmdStr = "ping -'c' '1' -'s' '24' 'www.google.com'";
        }

        self::assertEquals($cmdStr, $cmd1->getBuiltCommand());

        $cmd2 = new Command();
        $cmd2->setCommand('php')
            ->addArgument(__DIR__ . "/cmd.php")
            ->addArgument('--hasStdIn');

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
            ->addArgument(__DIR__ . "/cmd.php")
            ->addArgument('--otp', $otp);

        $cmd2 = new Command();
        $cmd2->setCommand('php')
            ->addArgument(__DIR__ . "/cmd.php")
            ->addArgument('--otp', PIPE_PH);


        $result = $cmd1->chain()->add($cmd2, null, true)->run();

        self::assertEquals($otp, $result[1]->getStdOut());
    }

    public function testPipeAsArgument2()
    {
        $otp = 'foobar';

        $cmd1 = new Command();
        $cmd1->setCommand('php')
            ->addArgument(__DIR__ . "/cmd.php")
            ->addArgument('--otp', $otp);

        $cmd2 = new Command();
        $cmd2->setCommand('php')
            ->addArgument(__DIR__ . "/cmd.php")
            ->addArgument(PIPE_PH);


        $result = $cmd1->chain()->add($cmd2, null, true)->run();

        $res = json_decode($result[1]->getStdOut());

        self::assertEquals($otp, $res->Args[1]);
    }
}