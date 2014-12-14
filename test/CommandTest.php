<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 13-12-2014
 * Time: 17:00
 */

namespace Tivie\Command;


class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OS
     */
    protected $os;

    /**
     * @var
     */
    protected $testClass;

    public function setUp()
    {
        // Since it's an utility class (needed for testing)
        // We assume it's "error free"
        $this->os = new OS();


    }

    /**
     * @covers \Tivie\Command\Command::setCommand
     * @covers \Tivie\Command\Command::getCommand
     */
    public function testSetGetCommand()
    {
        $cmd = new Command(null, $this->os);

        //Simple test
        $cmdName = 'foo';
        $cmd->setCommand($cmdName);
        self::assertEquals($cmdName, $cmd->getCommand());

        //Escape test
        $cmdName = 'foo; bar -baz';
        //Linux uses \; Windows uses ^
        $escapedName = escapeshellcmd($cmdName);
        $cmd->setCommand($cmdName);
        self::assertEquals($escapedName, $cmd->getCommand());
    }

    /**
     * @covers \Tivie\Command\Command::setCommand
     * @expectedException \Tivie\Command\Exception\InvalidArgumentException
     */
    public function testSetGetCommandException()
    {
        $cmd = new Command(null, $this->os);

        //Simple test
        $cmdName = 1;
        $cmd->setCommand($cmdName);
    }

    /**
     * @covers \Tivie\Command\Command::addArgument
     * @covers \Tivie\Command\Command::removeArgument
     * @covers \Tivie\Command\Command::getArgumentValue
     * @covers \Tivie\Command\Command::getArguments
     */
    public function testAddRemoveArguments()
    {
        $cmd = new Command(\Tivie\Command\DONT_ESCAPE, $this->os);
        $arg = 'foo';

        //Argument with no value
        $cmd->addArgument($arg);
        self::assertArrayHasKey($arg, $cmd->getArguments());
        $cmd->removeArgument($arg);
        self::assertArrayNotHasKey($arg, $cmd->getArguments());


        //Argument with just one value
        $value = 'foobar.txt';
        $cmd->addArgument($arg, $value);
        self::assertArrayHasKey($arg, $cmd->getArguments());
        self::assertEquals($value, $cmd->getArgumentValue($arg));

        //Argument with several values
        $value = array('foo.txt', 'bar.txt', 'baz.txt');
        $cmd->addArgument($arg, $value);
        self::assertArrayHasKey($arg, $cmd->getArguments());
        self::assertEquals($value, $cmd->getArgumentValue($arg));

        //Escape argument key and value
        $cmd = new Command(null, $this->os);
        $arg = 'foo&&bar';
        $value = 'somefoo';
        $cmd->addArgument($arg, $value);
        self::assertArrayHasKey(escapeshellcmd($arg), $cmd->getArguments());
        self::assertEquals(escapeshellarg($value), $cmd->getArgumentValue($arg));

        //unset escaped argument
        $cmd->removeArgument($arg);
        self::assertArrayNotHasKey(escapeshellcmd($arg), $cmd->getArguments());
        self::assertArrayNotHasKey($arg, $cmd->getArguments());
    }

    /**
     * @covers \Tivie\Command\Command::addArgument
     * @covers \Tivie\Command\Command::removeArgument
     * @covers \Tivie\Command\Command::getArgumentValue
     * @covers \Tivie\Command\Command::getArguments
     */
    public function testAddRemoveArgumentsWithPrepend()
    {
        $cmd = new Command(DONT_ESCAPE, $this->os);
        $value = 'foobar.txt';

        //Linux Style (one char switch)
        $arg = 'f';
        $cmd->addArgument($arg, $value, PREPEND_UNIX_STYLE);
        self::assertArrayHasKey("-$arg", $cmd->getArguments());
        self::assertEquals($value, $cmd->getArgumentValue($arg));
        self::assertArrayNotHasKey("--$arg", $cmd->getArguments());
        self::assertArrayNotHasKey("/$arg", $cmd->getArguments());

        $cmd->removeArgument($arg);
        self::assertArrayNotHasKey($arg, $cmd->getArguments());
        self::assertArrayNotHasKey("-$arg", $cmd->getArguments());

        //Linux Style (string arg)
        $arg = 'foo';
        $cmd->addArgument($arg, $value, PREPEND_UNIX_STYLE);
        self::assertArrayHasKey("--$arg", $cmd->getArguments());
        self::assertEquals($value, $cmd->getArgumentValue($arg));
        self::assertArrayNotHasKey("-$arg", $cmd->getArguments());
        self::assertArrayNotHasKey("/$arg", $cmd->getArguments());

        $cmd->removeArgument($arg);
        self::assertArrayNotHasKey($arg, $cmd->getArguments());
        self::assertArrayNotHasKey("--$arg", $cmd->getArguments());


        //Windows Style (one char switch)
        $arg = 'f';
        $cmd->addArgument($arg, $value, PREPEND_WINDOWS_STYLE);
        self::assertArrayHasKey("/$arg", $cmd->getArguments());
        self::assertEquals($value, $cmd->getArgumentValue("/$arg"));
        self::assertArrayNotHasKey("-$arg", $cmd->getArguments());
        self::assertArrayNotHasKey("--$arg", $cmd->getArguments());

        $cmd->removeArgument($arg);
        self::assertArrayNotHasKey($arg, $cmd->getArguments());
        self::assertArrayNotHasKey("/$arg", $cmd->getArguments());

        //Windows Style (one char switch)
        $arg = 'foo';
        $s = '/';
        $sArg = $s . $arg;

        $cmd->addArgument($arg, $value, PREPEND_WINDOWS_STYLE);
        self::assertArrayHasKey($sArg, $cmd->getArguments());
        self::assertEquals($value, $cmd->getArgumentValue($sArg));
        self::assertArrayNotHasKey("-$arg", $cmd->getArguments());
        self::assertArrayNotHasKey("--$arg", $cmd->getArguments());

        $cmd->removeArgument($arg);
        self::assertArrayNotHasKey($arg, $cmd->getArguments());
        self::assertArrayNotHasKey($sArg, $cmd->getArguments());
    }

    /**
     * @covers \Tivie\Command\Command::getStdIn
     * @covers \Tivie\Command\Command::setStdIn
     */
    public function testSetGetStdIn()
    {
        $cmd = new Command(null, $this->os);
        $stdIn = 'Some String and stuff';
        $cmd->setStdIn($stdIn);
        self::assertEquals($stdIn, $cmd->getStdIn());
    }

    /**
     * @covers \Tivie\Command\Command::addArgument
     * @covers \Tivie\Command\Command::setCommand
     * @covers \Tivie\Command\Command::getBuiltCommand
     * @covers \Tivie\Command\Command::__toString()
     */
    public function testGetBuiltCommand()
    {
        $cmd = new Command(null, $this->os);

        $a1K = 'bar';
        $a1V = 'barVal';
        $a2K = 'baz';
        $a2V = array('bazval1', 'bazval2');

        $cmd->setCommand('foo')
            ->addArgument($a1K, $a1V)
            ->addArgument($a2K, $a2V);

        $expCmd = 'foo '.$a1K.' '.escapeshellarg($a1V).' '.$a2K.' '.escapeshellarg($a2V[0]).' baz ' . escapeshellarg($a2V[1]);

        self::assertEquals($expCmd, $cmd->getBuiltCommand());
    }

    /**
     * @covers \Tivie\Command\Command::run
     */
    public function testRunCallsCorrectMethod()
    {
        $resMock = $this->getMockBuilder('\Tivie\Command\Result')->getMock();

        //TEST exec method is called in windows environment
        $cmd = $this->getCmdMock(null, OS_WINDOWS);
        $cmd->expects($this->once())->method('exec');
        $cmd->run($resMock);

        //TEST procOpen method is called in windows environment with flag set to FORCE_USE_PROC_OPEN
        $cmd = $this->getCmdMock(FORCE_USE_PROC_OPEN, OS_WINDOWS);
        $cmd->expects($this->once())->method('procOpen');
        $cmd->run($resMock);

        //TEST procOpen method is called in unix environment
        $cmd = $this->getCmdMock(FORCE_USE_PROC_OPEN, OS_NIX);
        $cmd->expects($this->once())->method('procOpen');
        $cmd->run($resMock);
    }

    private function getCmdMock($flags, $os)
    {
        $osMock = $this->getOSMock($os);
        $cmdMock = $this->getMockBuilder('\Tivie\Command\Command')
            ->setMethods(array('procOpen', 'exec'))
            ->setConstructorArgs(array($flags, $osMock))
            ->getMock();
        return $cmdMock;
    }

    /**
     * @param $os
     * @return \Tivie\Command\OS
     */
    private function getOSMock($os)
    {
        $mock = $this->getMockBuilder('\Tivie\Command\OS')
            ->setMethods(array('detect'))
            ->getMock();

        switch ($os) {
            case OS_WINDOWS:
                $mock->method('detect')->willReturn(OS_WINDOWS);
                break;
            case OS_NIX:
                $mock->method('detect')->willReturn(OS_NIX);
                break;
            default:
                trigger_error('SELECTED WRONG OS IN TEST');
        }

        return $mock;
    }

    /**
     * @covers \Tivie\Command\Command::run
     * @covers \Tivie\Command\Command::exec
     */
    public function testRunOnWindows()
    {
        //MOCK OS WINDOWS
        $osMock = $this->getOSMock(OS_WINDOWS);

        // Simulate running on windows (with exec)
        $cmd = new Command(null, $osMock);
        $expectedCmdOtp = 'hello';

        $mock = $this->getResultMock();

        $mock->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo($expectedCmdOtp));

        $cmd->setCommand('php')->addArgument('-r', "echo '$expectedCmdOtp';");

        $cmd->run($mock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Tivie\Command\Result
     */
    private function getResultMock()
    {
        $methods = array(
            'setStdIn',
            'setStdOut',
            'setStdErr',
            'setExitCode',
            'setLastLine');

        $mock = $this->getMockBuilder('\Tivie\Command\Result')
            ->setMethods($methods)
            ->getMock();

        foreach ($methods as $method) {
            $mock->method($method)
                ->willReturn($mock);
        }

        return $mock;
    }

    /**
     * @covers \Tivie\Command\Command::run
     * @covers \Tivie\Command\Command::procOpen
     */
    public function testRunOnUnix()
    {
        //MOCK OS UNIX
        $osMock = $this->getOSMock(OS_NIX);

        // Simulate running on Unix (with PROC_OPEN)
        $cmd = new Command(null, $osMock);
        $expectedCmdOtp = 'hello';

        $mock = $this->getResultMock();

        $mock->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo($expectedCmdOtp));

        $cmd->setCommand('php')->addArgument('-r', "echo '$expectedCmdOtp';");

        $cmd->run($mock);
    }

    /**
     * @covers \Tivie\Command\Command::chain
     */
    public function testChain()
    {
        $cmd = new Command();

        $chainMock = $this->getMockBuilder('\Tivie\Command\Chain')
            ->setMethods(array('add'))
            ->getMock();

        $chainMock->expects($this->once())->method('add')->with($this->equalTo($cmd));
        $cmd->chain($chainMock);
    }
}
