<?php

namespace Tests\Mockingbird\ExampleService;

/**
 * Class ExampleC.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Tests\Mockingbird\ExampleService
 */
class ExampleC
{
    /**
     * @var ExampleAInterface
     */
    protected $one;

    /**
     * @var ExampleA
     */
    protected $two;

    /**
     * Construct an instance of a ExampleC.
     *
     * @param ExampleAInterface $one
     * @param ExampleA $two
     */
    public function __construct(
        ExampleAInterface $one,
        ExampleA $two
    ) {
        $this->one = $one;
        $this->two = $two;
    }

    /**
     * Get one.
     *
     * @return ExampleAInterface
     */
    public function getOne(): ExampleAInterface
    {
        return $this->one;
    }

    /**
     * Get two.
     *
     * @return ExampleA
     */
    public function getTwo(): ExampleA
    {
        return $this->two;
    }
}
