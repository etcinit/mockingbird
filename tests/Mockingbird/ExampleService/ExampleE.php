<?php

declare(strict_types = 1);

namespace Tests\Mockingbird\ExampleService;

/**
 * Class ExampleE.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Tests\Mockingbird\ExampleService
 */
class ExampleE
{
    /**
     * @var ExampleA
     */
    private $exampleA;

    /**
     * @var ExampleC
     */
    private $exampleC;

    /**
     * Construct an instance of a ExampleE.
     *
     * @param ExampleA $exampleA
     * @param ExampleC $exampleC
     */
    public function __construct(ExampleA $exampleA, ExampleC $exampleC)
    {
        $this->exampleA = $exampleA;
        $this->exampleC = $exampleC;
    }

    public function someAction()
    {
        $this->exampleA->sayHello();
        $this->exampleC->getTwo();
    }

    public function someFluentAction()
    {
        $this->exampleA->fluentOne()->fluentTwo()->sayHello();
    }

    public function someSelfAction()
    {
        $this->exampleA->mirror($this->exampleA);
        $this->exampleC->getTwo()->mirror($this->exampleA);
    }

    /**
     * @param string $first
     * @param string $second
     *
     * @return string
     */
    public function concat(string $first, string $second = " world"): string
    {
        return $first . $second;
    }
}