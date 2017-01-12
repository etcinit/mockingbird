<?php

declare(strict_types = 1);

namespace Mockingbird;

/**
 * Class CallExpectation.
 *
 * A simple structure modeling a method call expectation.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Mockingbird
 */
class CallExpectation
{
    /**
     * @var string
     */
    protected $methodName;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var mixed|null
     */
    protected $return;

    /**
     * @var int
     */
    protected $times;

    /**
     * Construct an instance of a CallExpectation.
     *
     * @param string $methodName
     * @param array $arguments
     * @param mixed|null $return
     * @param int $times
     */
    public function __construct(
        string $methodName,
        array $arguments,
        $return = null,
        int $times = 1
    ) {
        $this->methodName = $methodName;
        $this->arguments = $arguments;
        $this->return = $return;
        $this->times = $times;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return mixed|null
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * @return int
     */
    public function getTimes(): int
    {
        return $this->times;
    }
}