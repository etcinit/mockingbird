<?php

declare(strict_types = 1);

namespace Mockingbird;

use LogicException;
use Mockingbird\Exceptions\ResolutionException;
use Closure;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use ReflectionParameter;

/**
 * Class Impersonator.
 *
 * Automatically builds and injects mocks for testing.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Mockingbird
 */
class Stage
{
    /**
     * List of provided mocks.
     *
     * @var array
     */
    protected $provided;

    /**
     * Construct an instance of an Impersonator.
     */
    public function __construct()
    {
        $this->provided = [];
    }

    /**
     * Attempt to build the provided class.
     *
     * Be aware that complex classes might not be resolved automatically.
     * For example, scalar types are currently not supported.
     *
     * @param string $target
     *
     * @throws ResolutionException
     * @return mixed
     */
    public function make($target)
    {
        $arguments = $this->getArgumentTypes($target);

        $resolved = $this->mockArguments($arguments);

        return new $target(...$resolved);
    }

    /**
     * Provide a mock.
     *
     * Here we do some "magic" to attempt to figure out what the mock
     * implements. In order for mock resolution to be fast, relationships
     * between types and mocks are stored on a hash table ($this->provided).
     * This means that if you have objects implementing the same interface or
     * that are instances of the same class, then the last object provided
     * will be the one used.
     *
     * For scenarios where you have two parameters of the same type in the
     * constructor or conflicting interfaces, it is recommended that you build
     * the object manually.
     *
     * @param mixed $mock
     *
     * @return Stage
     */
    public function provide($mock): Stage
    {
        if (is_string($mock) || is_array($mock)) {
            throw new LogicException(
                'A mock cannot be a string or an array.'
            );
        }

        $interfaces = class_implements($mock);
        $parents = class_parents($mock);

        foreach ($interfaces as $interface) {
            $this->provided[$interface] = $mock;
        }

        foreach ($parents as $parent) {
            $this->provided[$parent] = $mock;
        }

        $this->provided[get_class($mock)] = $mock;

        return $this;
    }

    /**
     * A shortcut for building mocks.
     *
     * @param string $type
     * @param Closure|CallExpectation[] $definition
     *
     * @return Stage
     */
    public function mock(string $type, $definition): Stage
    {
        $this->provide(mock($type, $definition));

        return $this;
    }

    /**
     * Reflect about a class' constructor parameter types.
     *
     * @param mixed $target
     *
     * @return ReflectionParameter[]
     */
    protected function getArgumentTypes($target): array
    {
        $reflect = new ReflectionClass($target);

        if ($reflect->getConstructor() === null) {
            return [];
        }

        return $reflect->getConstructor()->getParameters();
    }

    /**
     * Attempt to automatically mock the arguments of a function.
     *
     * @param ReflectionParameter[] $parameters
     * @param array $overrides
     *
     * @return array
     * @throws ResolutionException
     */
    protected function mockArguments(array $parameters, $overrides = []): array
    {
        $resolved = [];

        foreach ($parameters as $parameter) {
            $hint = $parameter->getClass();
            $name = $parameter->getName();

            if (array_key_exists($name, $overrides)) {
                $resolved[] = $overrides[$name];

                continue;
            }

            if (is_null($hint)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $resolved[] = $parameter->getDefaultValue();

                    continue;
                }

                throw new ResolutionException();
            }

            $mock = $this->resolveMock($hint);

            $resolved[] = $mock;
        }

        return $resolved;
    }

    /**
     * Resolve which mock instance to use.
     *
     * Here we mainly decide whether to use something that was provided to or
     * go ahead an build an empty mock.
     *
     * @param ReflectionClass $type
     *
     * @return MockInterface|mixed
     */
    protected function resolveMock(ReflectionClass $type)
    {
        $name = $type->getName();

        if (array_key_exists($name, $this->provided)) {
            return $this->provided[$name];
        }

        return $this->buildMock($type);
    }

    /**
     * Build an empty mock.
     *
     * Override this method if you would like to use a different mocking library
     * or if you would like all your mocks having some properties in common.
     *
     * @param ReflectionClass $type
     *
     * @return MockInterface
     */
    protected function buildMock(ReflectionClass $type): MockInterface
    {
        return mock($type->getName());
    }

    /**
     * Construct an instance of the target class and call the method while
     * injecting any argument that was not provided.
     *
     * @param string $target
     * @param string $methodName
     * @param array $arguments
     *
     * @return mixed
     */
    public function makeAndCall(
        string $target,
        string $methodName,
        array $arguments = []
    ) {
        return $this->call($this->make($target), $methodName, $arguments);
    }

    /**
     * Call the method on the target object while injecting any missing
     * arguments using objects defined on this Impersonator instance.
     *
     * This allows one to easily call methods that define dependencies in their
     * arguments rather than just on the constructor of the class they reside
     * in.
     *
     * Impersonator will apply a similar algorithm to make(). Dependencies that
     * are not provided, will be automatically be replaced with a dummy mock.
     * However, in the case of method calls, any provided argument will take
     * precedence over any injection.
     *
     * @param mixed $target
     * @param string $methodName
     * @param array $arguments
     *
     * @return mixed
     */
    public function call(
        $target,
        string $methodName,
        array $arguments = []
    ) {
        $reflection = new ReflectionClass($target);

        $resolved = $this->mockArguments(
            $reflection->getMethod($methodName)->getParameters(),
            $arguments
        );

        return $target->$methodName(...$resolved);
    }
}
