<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 13-12-2014
 * Time: 17:00
 */

namespace Tivie\Command;

use Tivie\OS\Detector;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Detector
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
        $this->os = new Detector();
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
     * @return \Tivie\OS\Detector
     */
    private function getOSMock($os)
    {
        $mock = $this->getMockBuilder('\Tivie\OS\Detector')
            ->setMethods(array('getType', 'getFamily', 'getKernelName', 'isWindowsLike', 'isUnixLike'))
            ->getMock();

        switch ($os) {
            case \Tivie\OS\WINDOWS_FAMILY:
                $mock->method('getType')->willReturn(\Tivie\OS\WINDOWS);
                $mock->method('getFamily')->willReturn(\Tivie\OS\WINDOWS_FAMILY);
                $mock->method('getKernelName')->willReturn('WINDOWS');
                $mock->method('isWindowsLike')->willReturn(true);
                $mock->method('isUnixLike')->willReturn(false);
                break;
            case \Tivie\OS\UNIX_FAMILY:
                $mock->method('getType')->willReturn(\Tivie\OS\LINUX);
                $mock->method('getFamily')->willReturn(\Tivie\OS\UNIX_FAMILY);
                $mock->method('getKernelName')->willReturn('LINUX');
                $mock->method('isWindowsLike')->willReturn(false);
                $mock->method('isUnixLike')->willReturn(true);
                break;
            default:
                trigger_error('SELECTED WRONG OS IN TEST');
        }

        return $mock;
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
            'setLastLine',
        );

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
     * @param null $key
     * @param null $values
     * @return \PHPUnit_Framework_MockObject_MockObject|Argument
     */
    private function getArgumentMock($key = null, $values = null)
    {
        $mock = $this->getMockBuilder('\Tivie\Command\Argument');

        if (is_null($key)) {
            $mock->disableOriginalConstructor();
        } else {
            $mock->setConstructorArgs(func_get_args());
        }

        return $mock->getMock();
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
        $cmdName = 'foo&& bar -baz';
        $cmd = new Command(ESCAPE, $this->os);
        //Linux uses \ Windows uses ^
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
     * @covers \Tivie\Command\Command::getArguments
     */
    public function testAddRemoveArguments()
    {
        $cmd = new Command(null, $this->os);
        $arg = $this->getArgumentMock();

        $cmd->addArgument($arg);
        $args = $cmd->getArguments();
        self::assertTrue(isset($args[0]));

        $cmd->removeArgument($arg);
        $args = $cmd->getArguments();
        self::assertTrue(!isset($args[0]));
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
            ->addArgument(new Argument($a1K, $a1V))
            ->addArgument(new Argument($a2K, $a2V));

        $expCmd = "foo $a1K $a1V $a2K $a2V[0] $a2K $a2V[1]";

        self::assertEquals($expCmd, $cmd->getBuiltCommand());
    }

    /**
     * @covers \Tivie\Command\Command::run
     */
    public function testRunCallsCorrectMethod()
    {
        $resMock = $this->getMockBuilder('\Tivie\Command\Result')->getMock();

        //TEST exec method is called in windows environment
        $cmd = $this->getCmdMock(null, \Tivie\OS\WINDOWS_FAMILY);
        $cmd->expects($this->once())->method('exec');
        $cmd->run($resMock);

        //TEST procOpen method is called in windows environment with flag set to FORCE_USE_PROC_OPEN
        $cmd = $this->getCmdMock(FORCE_USE_PROC_OPEN, \Tivie\OS\WINDOWS_FAMILY);
        $cmd->expects($this->once())->method('procOpen');
        $cmd->run($resMock);

        //TEST procOpen method is called in unix environment
        $cmd = $this->getCmdMock(FORCE_USE_PROC_OPEN, \Tivie\OS\UNIX_FAMILY);
        $cmd->expects($this->once())->method('procOpen');
        $cmd->run($resMock);
    }

    /**
     * @covers \Tivie\Command\Command::run
     * @covers \Tivie\Command\Command::exec
     */
    public function testRunOnWindows()
    {
        //MOCK OS WINDOWS
        $osMock = $this->getOSMock(\Tivie\OS\WINDOWS_FAMILY);

        // Simulate running on windows (with exec)
        $cmd = new Command(null, $osMock);
        $expectedCmdOtp = 'hello';
        $cmd->setCommand('php')->addArgument(new Argument('-r', "\"echo '$expectedCmdOtp';\"", null, false));

        $mock = $this->getResultMock();
        $mock->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo($expectedCmdOtp));

        $cmd->run($mock);
    }

    /**
     * @covers \Tivie\Command\Command::run
     * @covers \Tivie\Command\Command::procOpen
     */
    public function testRunOnUnix()
    {
        //MOCK OS UNIX
        $osMock = $this->getOSMock(\Tivie\OS\UNIX_FAMILY);

        // Simulate running on Unix (with PROC_OPEN)
        $cmd = new Command(ESCAPE, $osMock);
        $expectedCmdOtp = 'hello';
        $cmd->setCommand('php')->addArgument(new Argument('-r', "\"echo 'hello';\"", null, false));

        $mock = $this->getResultMock();

        $mock->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo($expectedCmdOtp));

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
    
    /**
     * @covers \Tivie\Command\Command::setFlags
     * @covers \Tivie\Command\Command::getFlags
     */
    public function testSetGetFlags()
    {
        $cmd = new Command();

        // Test 1
        $flags = FORCE_USE_PROC_OPEN | ESCAPE | DONT_ADD_SPACE_BEFORE_VALUE;
        $cmd->setFlags($flags);
        self::assertTrue( (bool) ($cmd->getFlags() & FORCE_USE_PROC_OPEN), "Flag FORCE_USE_PROC_OPEN was not set properly");
        self::assertTrue( (bool) ($cmd->getFlags() & ESCAPE), "Flag ESCAPE was not set properly");
        self::assertTrue( (bool) ($cmd->getFlags() & DONT_ADD_SPACE_BEFORE_VALUE), "Flag DONT_ADD_SPACE_BEFORE_VALUE was not set properly");


        // Test 2
        $flags = FORCE_USE_PROC_OPEN | DONT_ADD_SPACE_BEFORE_VALUE;
        $cmd->setFlags($flags);
        self::assertTrue( (bool) ($cmd->getFlags() & FORCE_USE_PROC_OPEN), "Flag FORCE_USE_PROC_OPEN was not set properly");
        self::assertFalse( (bool) ($cmd->getFlags() & ESCAPE), "Flag ESCAPE was not set properly");
        self::assertTrue( (bool) ($cmd->getFlags() & DONT_ADD_SPACE_BEFORE_VALUE), "Flag DONT_ADD_SPACE_BEFORE_VALUE was not set properly");

        //Test 3 (reset)
        $flags = 0;
        $cmd->setFlags($flags);
        self::assertFalse( (bool) ($cmd->getFlags() & FORCE_USE_PROC_OPEN), "Flag FORCE_USE_PROC_OPEN was not set properly");
        self::assertFalse( (bool) ($cmd->getFlags() & ESCAPE), "Flag ESCAPE was not set properly");
        self::assertFalse( (bool) ($cmd->getFlags() & DONT_ADD_SPACE_BEFORE_VALUE), "Flag DONT_ADD_SPACE_BEFORE_VALUE was not set properly");
    }

    /**
     * @expectedException \Tivie\Command\Exception\InvalidArgumentException
     * @covers  \Tivie\Command\Command::setFlags
     */
    public function testSetFlagsIAException()
    {
        $cmd = new Command();
        $cmd->setFlags('foo');
    }

    /**
     * @covers \Tivie\Command\Command::chdir
     * @covers \Tivie\Command\Command::setCurrentWorkingDirectory
     * @covers \Tivie\Command\Command::exec
     * @covers \Tivie\Command\Command::proc_open
     */
    public function testChdir()
    {
        $s = DIRECTORY_SEPARATOR;
        $dir = realpath(__DIR__ . $s. "..". $s . "dir" . $s . "test" . $s);

        if(!$dir) {
            // something went wrong setting the relative path so we inform and quick gracefully
            fwrite(STDERR, "CommandTest::testChdir() - Failed to discover the test directory in testChdir so the ".
                "test was skipped");
            return;
        }

        $cmd = new Command();

        if ($this->os->isWindowsLike()) {
            $cmd->setCommand('cd');
        } else {
            $cmd->setCommand('pwd');
        }

        // Test with no working directory
        $result = $this->getResultMock();
        $result->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo(realpath(getcwd())));

        $cmd->run($result);

        // Test with working directory
        $cmd->setCurrentWorkingDirectory($dir);

        //Test with exec
        $cmd->setFlags(FORCE_USE_EXEC);

        $result = $this->getResultMock();
        $result->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo($dir));

        $cmd->run($result);

        //Test with proc_open
        $cmd->setFlags(FORCE_USE_PROC_OPEN);

        $result = $this->getResultMock();
        $result->expects($this->once())
            ->method('setStdOut')
            ->with($this->equalTo($dir));

        $cmd->run($result);
    }
}
