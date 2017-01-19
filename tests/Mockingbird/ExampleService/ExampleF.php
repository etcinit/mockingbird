<?php

namespace Tests\Mockingbird\ExampleService;

/**
 * Class ExampleF.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 *
 * @package Tests\Mockingbird\ExampleService
 */
class ExampleF
{
    /**
     * Construct an instance of a ExampleF.
     *
     * @param ExampleA $exampleA
     */
    public function __construct(ExampleA $exampleA)
    {
        $exampleA->sayHello();
    }

    /**
     * @param ExampleA $exampleA
     *
     * @return string
     */
    public function getOne(ExampleA $exampleA): string
    {
        return $exampleA->sayHello();
    }
}