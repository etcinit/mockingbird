# Mockery

Mockingbird uses Mockery for creating Mocks, however, you generally don't have
to interact with it directly. Below you can find how some Mockery concepts can
be written using Mockingbird's DSL:

## Mocks and expectations

One of the main objectives of this library is to provides an alternative compact
syntax to defining call expectations on mocks. While the following two examples
are equivalent, Mockery's API reads like a sentence but takes more space, and
Mockingbird's DSL is compact but sacrifices some legibility.

Mockingbird provides two main expectation constructors:

- `on(methodName, arguments, returnValue, times)`
- `throwOn(methodName, arguments, exceptionClass, exceptionMessage, code)`

Mocks are built using `mock(targetType, expectations)` which takes the name of
the mocked class and an array of expectations.

If `on` and `throwOn` do not allow you to express an expectation, you can always
fallback to the Mockery API by providing a Closure with the following signature:

`function (MockInterface $mock) { /* Add expectations */ }`

as either an item in the expectations array or as the expectations itself:

- `mock(ExampleA::class, [function (MockInterface $mock) { /* ... */ }, ...])`
- `mock(ExampleA::class, function (MockInterface $mock) { /* ... */ })`

**Mockery:**

```php 
use Mockery;

$myMock = Mockery::mock(ExampleA::class);

$myMock->shouldReceive('send')->with('my message')->twice()->andReturn('ok');
$myMock->shouldReceive('receive')->with('channel2')->andReturn('ok');
```

**Mockingbird:**

```php
use function Mockingbird\{mock, on};

$myMock = mock(ExampleA::class, [
    on('send', ['my message'], 'ok', 2),
    on('receive', ['channel2'], 'ok'),
]);
```

## Matchers

The same applies to argument matchers. On Mockingbird, `match` covers both 
`Mockery::type` and `Mockery::on`:

- `Mockery::type('array')` -> `match('array')`.
- `Mockery::on(function () {})` -> `match(function () {})`.

## Matching and returning self

On Mockery, you can use `andReturnSelf` to create expectations for fluent APIs.
Mockingbird also supports this scenario by using `self()`:

```php
use function Mockingbird\{mock, on, self};

$myMock = mock(ExampleA::class, [
    on('receive', ['channel2'], self()),
]);
```

Additionally, `self()` also works as an argument matcher:

```php
use function Mockingbird\{mock, on, self};

$myMock = mock(ExampleA::class, [
    // Expects an intance of ExampleA as an argument.
    on('send', [self()], 'ok', 2),
]);
```
