# Stages

The `Stage` class is the central piece of Mockingbird. It is a small dependency
container, but it does not have to do as much work as others since it will
mainly build dependencies one level deep.

A `Stage` instance can be constructed with `new` but you can also use the
`stage()` shortcut function. Each instance of a stage is independent from each
other, so you can set up as many scenarios as you need.

When using Mockingbird, your workflow will look roughly like this:

```php 
stage()
    // Part 1: Provide and define dependencies.
    ->provide(...)
    ->mock(...)
    // Part 2: Construct a class and/or call a method.
    ->makeAndCall(...)
```

## Providing dependencies, or not...

Whenever it cannot find a registered dependency, `Stage` will automatically
create an empty mock for that dependency. This simple feature can actually save
a lot of boilerplate since you don't need to create and pass these mocks to
your tested classes if they are not going to be used during the test.

If you need to use a dependency, you can provide an actual implementation using
the `->provide()` method, or define a new mock inline using `->mock()`.

TL;DR:

- No definition: An empty mock is created automatically for the dependency.

- `->provide($someInstance)`: Provides a real instance of that dependency.

- `->mock($targetType, $expectations)`: The equivalent of calling
  `->provide(mock(...))`.

## Types of injection

Mockingbird handles dependency injection at both the constructor and method
level. While constructor injection is the most common pattern, method DI is
used by some frameworks (e.g. Controllers in Laravel).

Once you have defined and provided all your dependencies, you probably want to
use them on your target class or method. Mockingbird provides a few options:

- `->make($className)` performs constructor-level dependency injection.
- `->call($instance, $method, $arguments)` performs method-level dependency
  attempt to use the default value of an argument, if an override is not
  provided. Failure to fulfill these function arguments results in a
  `ResolutionException`.
- `->makeAndCall($className, $method, $arguments)` combines both methods into
  one. Performs both constructor and method dependency injection. It's shortcut
  for `$stage->call($stage->make($className), $method, $arguments)`.
  
`call` and `makeAndCall` will return the value returned by the method called.

## Limitations of the magic

Generally, Mockingbird can successfully build your services, but there are
still some edge cases that Mockingbird cannot yet cover or where there might be
unexpected results:

- Classes that depend on two or more instances of the same class.
- Classes with scalar or untyped arguments in their constructors.
