<?php

namespace Tests\Mockingbird;

use LogicException;
use Mockery as m;
use Mockery\MockInterface;
use Mockingbird\Exceptions\ResolutionException;
use function Mockingbird\{ stage, on, mock, self };
use const Mockingbird\{ SCOPE_CONSTRUCTOR, SCOPE_FUNCTION };
use PHPUnit_Framework_TestCase;
use Tests\Mockingbird\ExampleService\ExampleA;
use Tests\Mockingbird\ExampleService\ExampleAInterface;
use Tests\Mockingbird\ExampleService\ExampleB;
use Tests\Mockingbird\ExampleService\ExampleC;
use Tests\Mockingbird\ExampleService\ExampleD;
use Tests\Mockingbird\ExampleService\ExampleE;
use Tests\Mockingbird\ExampleService\ExampleF;

/**
 * Class StageTest.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Tests\Mockingbird\ExampleService
 */
class StageTest extends PHPUnit_Framework_TestCase
{
    public function testMake()
    {
        $stage = stage();

        $this->assertTrue($stage->make(ExampleB::class) instanceof ExampleB);

        /** @var ExampleC $result */
        $result = $stage->make(ExampleC::class);

        $this->assertFalse($result->getOne() === $result->getTwo());

        $stage->provide(new ExampleA());
        /** @var ExampleC $result */
        $result = $stage->make(ExampleC::class);

        $this->assertTrue($result->getOne() === $result->getTwo());

        $stage->provide(new ExampleA());
        $stage->provide(m::mock(ExampleAInterface::class));
        /** @var ExampleC $result */
        $result = $stage->make(ExampleC::class);

        $this->assertTrue($result->getOne() !== $result->getTwo());

        $stage->mock(ExampleA::class, function (MockInterface $mock) {
            $mock->shouldReceive('sayHello')->andReturn('Goodbye')->once();
        });
        /** @var ExampleC $result */
        $result = $stage->make(ExampleC::class);

        $this->assertTrue($result->getOne() === $result->getTwo());
        $this->assertEquals('Goodbye', $result->getTwo()->sayHello());
    }

    public function testMakeWithResolutionIssue()
    {
        $stage = stage();

        $this->expectException(ResolutionException::class);

        $stage->make(ExampleD::class);
    }

    public function testProvide()
    {
        $stage = stage();

        $instanceA = new ExampleA();
        $stage->provide($instanceA);

        /** @var ExampleB $instanceB */
        $instanceB = $stage->make(ExampleB::class);
        $this->assertEquals($instanceA, $instanceB->getExampleA());
    }

    public function testProvideWithString()
    {
        $stage = stage();

        $this->expectException(LogicException::class);

        $stage->provide(ExampleA::class);
    }

    public function testFunctions()
    {
        stage()
            ->provide(new ExampleA())
            ->mock(ExampleC::class, [
                on('getTwo', [], mock(ExampleA::class)),
            ])
            ->makeAndCall(ExampleE::class, 'someAction');
    }

    public function testSelfReturn()
    {
        stage()
            ->mock(ExampleA::class, [
                on('fluentOne', [], self()),
                on('fluentTwo', [], self()),
                on('sayHello', [], 'hello world'),
            ])
            ->makeAndCall(ExampleE::class, 'someFluentAction');
    }

    public function testSelfArgument()
    {
        stage()
            ->mock(ExampleA::class, [
                on('mirror', [self()]),
            ])
            ->mock(ExampleC::class, [
                on('getTwo', [], mock(ExampleA::class, [
                    on('mirror', [self()]),
                ])),
            ])
            ->makeAndCall(ExampleE::class, 'someSelfAction');
    }

    public function testMakeAndCallWithDefaultArguments()
    {
        $result1 = stage()
            ->makeAndCall(ExampleE::class, 'concat', ['first' => 'hello']);
        $result2 = stage()->makeAndCall(ExampleE::class, 'concat', [
            'first' => 'hi ',
            'second' => 'friend',
        ]);

        $this->assertEquals('hello world', $result1);
        $this->assertEquals('hi friend', $result2);
    }

    public function testMakeAndCallWithScope()
    {
        $result = stage()
            ->mock(ExampleA::class, [
                on('sayHello', [], 'a')
            ], SCOPE_CONSTRUCTOR)
            ->mock(ExampleA::class, [on('sayHello', [], 'b')], SCOPE_FUNCTION)
            ->makeAndCall(ExampleF::class, 'getOne');

        $this->assertEquals('b', $result);

        $result = stage()
            ->mock(ExampleA::class, [on('sayHello', [], 'a')])
            ->mock(ExampleA::class, [on('sayHello', [], 'b')], SCOPE_FUNCTION)
            ->makeAndCall(ExampleF::class, 'getOne');

        $this->assertEquals('b', $result);

        $result = stage()
            ->mock(ExampleA::class, [on('sayHello', [], 'a')])
            ->mock(ExampleA::class, [
                on('sayHello', [], 'b'),
            ], SCOPE_CONSTRUCTOR)
            ->makeAndCall(ExampleF::class, 'getOne');

        $this->assertEquals('a', $result);

        stage()
            ->mock(ExampleA::class, [
                on('sayHello', [], 'b'),
            ], SCOPE_CONSTRUCTOR)
            ->make(ExampleF::class);
    }
}
