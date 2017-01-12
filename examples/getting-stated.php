<?php

include_once __DIR__ . '/../vendor/autoload.php';

use function Mockingbird\{ stage, on, mock, self };

class DependencyA {
    private $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string { return $this->prefix; }
};
class DependencyB {};
class DependencyC {
    public function sayWorld(string $postfix): string {
        return 'world' . $postfix;
    }
}

class Service {
    /**
     * @var DependencyA
     */
    private $a;

    public function __construct(DependencyA $a, DependencyB $b) {
        $this->a = $a;
    }

    public function targetMethod(DependencyC $c): string
    {
        return $this->a->getPrefix() . 'hello ' . $c->sayWorld('!');
    }
};

// Out Service class has three dependencies, two services injected through the
// constructor and one passed on the called method. We will build a stage that
// provides them for us:
//
// - DependencyA: We will pass down a real instance (not a mock).
// - DependencyB: We will let Stage auto-mock it for us.
// - DependencyC: We will manually create our own mock.
//
$result = stage()
    ->provide(new DependencyA('>>> '))
    ->mock(DependencyC::class, [
        on('sayWorld', ['!'], 'worlds!!!'),
    ])
    ->makeAndCall(Service::class, 'targetMethod');

// Should output ">>> hello worlds!!!"
echo $result;

