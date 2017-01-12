<?php

namespace Tests\Mockingbird\ExampleService;

/**
 * Class ExampleA.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Tests\Mockingbird\ExampleService
 */
class ExampleA extends ExampleBase implements ExampleAInterface
{
    /**
     * Says hello.
     *
     * @return string
     */
    public function sayHello(): string
    {
        return 'hello there';
    }

    /**
     * @return ExampleA
     */
    public function fluentOne(): ExampleA
    {
        return $this;
    }

    /**
     * @return ExampleA
     */
    public function fluentTwo(): ExampleA
    {
        return $this;
    }

    /**
     * @param ExampleA $exampleA
     */
    public function mirror(ExampleA $exampleA)
    {
        //
    }
}
