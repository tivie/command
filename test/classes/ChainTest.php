<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 13-12-2014
 * Time: 17:00
 */

namespace Tivie\Command;

class ChainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Tivie\Command\Chain::add
     * @covers \Tivie\Command\Chain::run
     */
    public function testRun()
    {
        $chain = new Chain();

        $result1 = $this->getResultMock('foo', 1);
        $cmd1 = $this->getCmdMock();
        $cmd1->expects($this->once())->method('run')->willReturn($result1);
        $chain->add($cmd1);

        $cmd2 = $this->getCmdMock();
        $cmd2->expects($this->never())->method('run');
        $chain->add($cmd2, RUN_IF_PREVIOUS_SUCCEEDS);

        $result3 = $this->getResultMock('bar', 0);
        $cmd3 = $this->getCmdMock();
        $cmd3->expects($this->once())->method('run')->willReturn($result3);
        $chain->add($cmd3, RUN_IF_PREVIOUS_FAILS);

        $result4 = $this->getResultMock('baz', 0);
        $cmd4 = $this->getCmdMock();
        $cmd4->expects($this->once())->method('setStdIn')->with($this->equalTo('bar'));
        $cmd4->expects($this->once())->method('run')->willReturn($result4);
        $chain->add($cmd4, RUN_IF_PREVIOUS_SUCCEEDS, true);

        $results = $chain->run();

        self::assertSame($result1, $results[0]);
        self::assertSame($result3, $results[1]);
        self::assertSame($result4, $results[2]);
    }

    private function getResultMock($stdOut, $exitCode = 0)
    {
        $mock = $this->getMockBuilder('\Tivie\Command\Command')
            ->setMethods(array('getExitCode', 'getStdOut'))
            ->getMock();

        $mock->method('getExitCode')->willReturn($exitCode);
        $mock->method('getStdOut')->willReturn($stdOut);

        return $mock;
    }

    private function getCmdMock()
    {
        return $this->getMockBuilder('\Tivie\Command\Command')
            ->setMethods(array('run', 'setStdIn'))
            ->getMock();
    }

    /**
     * @covers \Tivie\Command\Chain::add
     * @covers \Tivie\Command\Chain::run
     */
    public function testRunPipedArgument()
    {
        $chain = new Chain();

        $xArg = 'foo';

        $result1 = $this->getResultMock($xArg, 0);
        $cmd1 = $this->getCmdMock();
        $cmd1->expects($this->once())->method('run')->willReturn($result1);
        $chain->add($cmd1);

        $cmd2 = $this->getCmdMock();
        $arg1 = $this->getArgumentMock('bar', array(PIPE_PH));
        $arg1->expects($this->once())->method('replaceValue')->with(0, $xArg);
        $cmd2->addArgument($arg1);

        $chain->add($cmd2, RUN_IF_PREVIOUS_SUCCEEDS, true);

        $chain->run();
    }

    private function getArgumentMock()
    {
        return $this->getMockBuilder('\Tivie\Command\Argument')
            ->setMethods(array('replaceValue'))
            ->setConstructorArgs(func_get_args())
            ->getMock();
    }
}
