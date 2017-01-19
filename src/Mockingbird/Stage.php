<?php

declare(strict_types = 1);

namespace Mockingbird;

use InvalidArgumentException;
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
     * Table of provided mocks.
     *
     * @var array
     */
    protected $provided;

    /**
     * Table of provided mock in scopes.
     *
     * @var array
     */
    protected $scopes;

    /**
     * Construct an instance of an Impersonator.
     */
    public function __construct()
    {
        $this->provided = [];
        $this->scopes = [
            SCOPE_CONSTRUCTOR => [],
            SCOPE_FUNCTION => [],
        ];
    }

    /**
     * Attempt to build the provided class.
     *
     * Be aware that complex classes might not be resolved automatically.
     * For example, scalar types are currently not supported.
     *
     * @param string $target
     * @param array $overrides
     *
     * @return mixed
     */
    public function make($target, array $overrides = [])
    {
        $arguments = $this->getArgumentTypes($target);

        $resolved = $this->mockArguments(
            $arguments,
            $overrides,
            SCOPE_CONSTRUCTOR
        );

        return new $target(...$resolved);
    }

    /**
     * Provide a mock or implementation.
     *
     * Here we do some "magic" to attempt to figure out what the mock
     * implements. In order for mock resolution to be fast, relationships
     * between types and mocks are stored on a hash table ($this->provided).
     *
     * This means that if you have objects implementing the same interface or
     * that are instances of the same class, then the last object provided
     * will be the one used.
     *
     * For scenarios where you have two parameters of the same type in the
     * constructor or conflicting interfaces, it is recommended that you build
     * the object manually.
     *
     * @param mixed $mock
     * @param string $scope
     *
     * @return Stage
     */
    public function provide($mock, string $scope = null): Stage
    {
        if (is_string($mock) || is_array($mock)) {
            throw new LogicException(
                'A mock cannot be a string or an array.'
            );
        }

        $mappings = $this->extractMappings($mock);

        if ($scope !== null) {
            if (!array_key_exists($scope, $this->scopes)) {
                $this->throwInvalidScopeException($scope);
            }

            $this->scopes[$scope] = array_merge(
                $this->scopes[$scope],
                $mappings
            );
        } else {
            $this->provided = array_merge($this->provided, $mappings);
        }

        return $this;
    }

    /**
     * Creates a map of all the interfaces and classes a type covers, which is
     * use for resolving which mock types to use.
     *
     * @param mixed $mock
     *
     * @return array
     */
    protected function extractMappings($mock): array
    {
        $interfaces = class_implements($mock);
        $parents = class_parents($mock);

        $mappings = [];

        foreach ($interfaces as $interface) {
            $mappings[$interface] = $mock;
        }

        foreach ($parents as $parent) {
            $mappings[$parent] = $mock;
        }

        $mappings[get_class($mock)] = $mock;

        return $mappings;
    }

    /**
     * A shortcut for building mocks.
     *
     * @param string $type
     * @param Closure|CallExpectation[] $definition
     * @param string $scope
     *
     * @return Stage
     */
    public function mock(string $type, $definition, string $scope = null): Stage
    {
        $this->provide(mock($type, $definition), $scope);

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
     * @param string $scope
     *
     * @return array
     * @throws ResolutionException
     */
    protected function mockArguments(
        array $parameters,
        array $overrides = [],
        string $scope = null
    ): array
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
                $resolved[] = $this
                    ->resolveNonHintedArgument($parameter, $scope);

                continue;
            }

            $mock = $this->resolveMock($hint, $scope);

            $resolved[] = $mock;
        }

        return $resolved;
    }

    /**
     * Attempts to resolve non-hinted arguments by looking up provided values
     * by name or default values.
     *
     * @param ReflectionParameter $parameter
     * @param string|null $scope
     *
     * @return mixed
     * @throws ResolutionException
     */
    protected function resolveNonHintedArgument(
        ReflectionParameter $parameter,
        string $scope = null
    ) {
        $name = '$' . $parameter->getName();

        // First, we attempt to find a scoped definition of the argument.
        if ($scope !== null) {
            if (!array_key_exists($scope, $this->scopes)) {
                $this->throwInvalidScopeException($scope);
            } elseif (array_key_exists($name, $this->scopes[$scope])) {
                return $this->scopes[$scope][$name];
            }
        }

        // Second, we look up on the global table.
        if (array_key_exists($name, $this->provided)) {
            return $this->provided[$name];
        }

        // Finally, we look for a default value.
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ResolutionException();
    }

    /**
     * Resolve which mock instance to use.
     *
     * Here we mainly decide whether to use something that was provided to or
     * go ahead an build an empty mock.
     *
     * @param ReflectionClass $type
     * @param string $scope
     *
     * @return mixed|MockInterface
     */
    protected function resolveMock(ReflectionClass $type, string $scope = null)
    {
        $name = $type->getName();

        // First, we attempt to find a scoped mock.
        if ($scope !== null) {
            if (!array_key_exists($scope, $this->scopes)) {
                $this->throwInvalidScopeException($scope);
            } elseif (array_key_exists($name, $this->scopes[$scope])) {
                return $this->scopes[$scope][$name];
            }
        }

        // Second, we lookup on the global table.
        if (array_key_exists($name, $this->provided)) {
            return $this->provided[$name];
        }

        // Finally, if we don't have a predefined mock, we create an empty one.
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
     * Throws an exception describing that the provided scope is unknown or
     * unsupported.
     *
     * @param string $scope
     */
    protected function throwInvalidScopeException(string $scope)
    {
        throw new InvalidArgumentException(vsprintf(
            'Unknown or unsupported scope "%s" provided.',
            [$scope]
        ));
    }

    /**
     * Provides the value of a constructor or function argument.
     *
     * @param $name
     * @param $value
     * @param string $scope
     *
     * @return Stage
     */
    public function set($name, $value, string $scope = null): Stage
    {
        // If a scope is provided, we will set the value in a scope.
        if ($scope !== null) {
            if (!array_key_exists($scope, $this->scopes)) {
                $this->throwInvalidScopeException($scope);
            }

            $this->scopes[$scope]['$' . $name] = $value;

            return $this;
        }

        $this->provided['$' . $name] = $value;

        return $this;
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
            $arguments,
            SCOPE_FUNCTION
        );

        return $target->$methodName(...$resolved);
    }
}
