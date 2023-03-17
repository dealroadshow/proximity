<?php

namespace Dealroadshow\Proximity\MethodsInterception;

use Dealroadshow\Proximity\ProxyInterface;

interface ResultInterceptorInterface
{
    public function afterMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs, InterceptionContext $context): void;
}
