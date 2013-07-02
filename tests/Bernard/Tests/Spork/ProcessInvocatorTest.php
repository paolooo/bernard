<?php

namespace Bernard\Tests\Spork;

use Bernard\Message\Envelope;
use Bernard\Message\DefaultMessage;
use Bernard\ServiceResolver\Invocator;
use Bernard\Spork\ProcessInvocator;
use Bernard\Tests\Fixtures;
use Spork\ProcessManager;

class ProcessInvocatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->invocator = $this->getMockBuilder('Bernard\ServiceResolver\Invocator')
            ->disableOriginalConstructor()->getMock();
        $this->spork = $this->getMock('Spork\ProcessManager');
    }

    public function testItsAnInvocator()
    {
        $this->assertInstanceOf('Bernard\ServiceResolver\Invocator', new ProcessInvocator($this->spork, $this->invocator));
    }

    public function testItForksWhenInvoked()
    {
        $invocator = new ProcessInvocator($this->spork, $this->invocator);

        $fork = $this->getMockBuilder('Spork\Fork')->disableOriginalConstructor()->getMock();
        $fork->expects($this->once())->method('wait');
        $fork->expects($this->once())->method('fail')->with($this->equalTo(array($invocator, 'fail')));

        $this->spork->expects($this->once())->method('fork')
            ->with($this->equalTo(array($this->invocator, 'invoke')))->will($this->returnValue($fork));

        $invocator->invoke();
    }

    public function testExceptionsAreConvertedToProcessLogicException()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('The extension "PCNTL" is required for forking to work.');
        }

        $this->setExpectedException('Bernard\Spork\Exception\ProcessException');

        $service = new Fixtures\Service;
        $envelope = new Envelope(new DefaultMessage('FailSendMessage'));

        $invocator = new Invocator($service, $envelope);

        $forking = new ProcessInvocator(new ProcessManager(), $invocator);
        $forking->invoke();
    }
}
