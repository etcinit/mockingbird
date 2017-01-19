<?php

namespace Mockingbird;

use Closure;
use Mockery;
use Mockery\Matcher\MatcherAbstract;
use Mockery\MockInterface;

const SCOPE_CONSTRUCTOR = 'constructor';
const SCOPE_FUNCTION = 'function';

if (!function_exists('Mockingbird\stage')) {
    /**
     * Construct a new stage.
     *
     * @return Stage
     */
    function stage(): Stage {
        return new Stage();
    }
}

if (!function_exists('Mockingbird\on')) {
    /**
     * Shortcut for constructing an instance of a CallExpectation.
     *
     * @param string $methodName
     * @param array $arguments
     * @param mixed|null $return
     * @param int $times
     *
     * @return CallExpectation
     */
    function on(
        string $methodName,
        array $arguments,
        $return = null,
        int $times = 1
    ): CallExpectation {
        return new CallExpectation($methodName, $arguments, $return, $times);
    }
}

if (!function_exists('Mockingbird\throwOn')) {
    /**
     * Shortcut for constructing an instance of a ThrowExpectation.
     *
     * @param string $methodName
     * @param array $arguments
     * @param string $exceptionClass
     * @param string $exceptionMessage
     * @param int $exceptionCode
     *
     * @return ThrowExpectation
     */
    function throwOn(
        string $methodName,
        array $arguments,
        $exceptionClass,
        string $exceptionMessage = '',
        int $exceptionCode = 0
    ): ThrowExpectation {
        return new ThrowExpectation(
            $methodName,
            $arguments,
            $exceptionClass,
            $exceptionMessage,
            $exceptionCode
        );
    }
}

if (!function_exists('Mockingbird\mock')) {
    /**
     * Build a Mockery mock using an array of expectations.
     *
     * @param string $type
     * @param Closure|array $expectations
     *
     * @return MockInterface
     */
    function mock(string $type, $expectations = []): MockInterface {
        if ($expectations instanceof Closure) {
            return Mockery::mock($type, $expectations);
        }

        return Mockery::mock(
            $type,
            function (MockInterface $mock) use ($type, $expectations) {
                foreach ($expectations as $expect) {
                    // Allow traditional Mockery expectations.
                    if ($expect instanceof Closure) {
                        $expect($mock);

                        continue;
                    }

                    $arguments = array_map(function ($arg) use ($type) {
                        if ($arg instanceof SelfReturn) {
                            return match($type);
                        }

                        return $arg;
                    }, $expect->getArguments());

                    $mockExpect = $mock
                        ->shouldReceive($expect->getMethodName())
                        ->times($expect->getTimes())
                        ->withArgs($arguments);

                    if ($expect->getReturn() instanceof SelfReturn) {
                        $mockExpect->andReturnSelf();
                    } else {
                        $mockExpect->andReturn($expect->getReturn());
                    }

                    if ($expect instanceof ThrowExpectation) {
                        $mockExpect->andThrow(
                            $expect->getExceptionClass(),
                            $expect->getExceptionMessage(),
                            $expect->getExceptionCode()
                        );
                    }
                }
            }
        );
    }
}

if (!function_exists('Mockingbird\match')) {
    /**
     * A shortcut for building argument matchers.
     *
     * @param $type
     *
     * @return MatcherAbstract
     */
    function match($type): MatcherAbstract {
        if ($type instanceof Closure) {
            return Mockery::on($type);
        }

        return Mockery::type($type);
    }
}

if (!function_exists('Mockingbird\self')) {
    /**
     * Used to refer to the class itself when building expectations, works as
     * both a matcher and a return type.
     *
     * @return SelfReturn
     */
    function self(): SelfReturn
    {
        return new SelfReturn();
    }
}