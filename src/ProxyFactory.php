<?php

namespace Dealroadshow\Proximity;

class ProxyFactory
{
    private array $proxyClassesCache = [];

    public function __construct(private readonly ProxyGenerator $generator, private readonly string $proxyClassesDirectory)
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
        $fileName = 'ProximityProxy'.$class->getName().bin2hex(random_bytes(3)).'.php';
        $fullPath = $this->proxyClassesDirectory.'/'.$fileName;

        file_put_contents($fullPath, $generatedProxy->code);

        require($fullPath);

        $proxyClass = $generatedProxy->fqcn;

        return new $proxyClass($object, $options);
    }
}
