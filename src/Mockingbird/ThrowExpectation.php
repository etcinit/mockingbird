<?php

declare(strict_types = 1);

namespace Mockingbird;

/**
 * Class ThrowExpectation.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Mockingbird
 */
class ThrowExpectation extends CallExpectation
{
    /**
     * @var mixed|null
     */
    protected $exceptionClass;

    /**
     * @var string
     */
    protected $exceptionMessage;

    /**
     * @var int
     */
    protected $exceptionCode;

    /**
     * Construct an instance of a CallAndThrowExpectation.
     *
     * @param string $methodName
     * @param array $arguments
     * @param mixed|null $exceptionClass
     * @param string $exceptionMessage
     * @param int $exceptionCode
     */
    public function __construct(
        string $methodName,
        array $arguments,
        $exceptionClass,
        string $exceptionMessage = '',
        int $exceptionCode = 0
    ) {
        parent::__construct($methodName, $arguments, null, 1);

        $this->exceptionClass = $exceptionClass;
        $this->exceptionMessage = $exceptionMessage;
        $this->exceptionCode = $exceptionCode;
    }

    /**
     * @return mixed|null
     */
    public function getExceptionClass()
    {
        return $this->exceptionClass;
    }

    /**
     * @return string
     */
    public function getExceptionMessage(): string
    {
        return $this->exceptionMessage;
    }

    /**
     * @return int
     */
    public function getExceptionCode(): int
    {
        return $this->exceptionCode;
    }
}