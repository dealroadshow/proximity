<?php

namespace Dealroadshow\Proximity;

use Dealroadshow\Proximity\ProxyStrategy\ProxyStrategyInterface;

class ProxyFactory
{
    private array $proxyClassesCache = [];

    public function __construct(private readonly ProxyGenerator $generator, private ProxyStrategyInterface $proxyStrategy)
    {
    }

    public function proxy(object $object, ProxyOptions $options = null): ProxyInterface
    {
        if ($object instanceof ProxyInterface) {
            throw new \InvalidArgumentException('$object is already a proxy');
        }

        if (null === $options) {
            $options = new ProxyOptions([], []);
        }

        $class = new \ReflectionObject($object);
        $proxyClass = $this->proxyClassesCache[$class->getName()] ?? null;
        if (null !== $proxyClass) {
            return new $proxyClass($object, $options);
        }

        $generatedProxy = $this->generator->generate($class);
        $proxyClass = $generatedProxy->fqcn;
        
        if (!class_exists($proxyClass, false)) {
            $this->proxyStrategy->apply($generatedProxy);
        }

        $this->proxyClassesCache[$class->getName()] = $proxyClass;

        return new $proxyClass($object, $options);
    }
}
