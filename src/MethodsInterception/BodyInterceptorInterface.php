<?php

namespace Dealroadshow\Proximity\MethodsInterception;

use Dealroadshow\Proximity\ProxyInterface;

interface BodyInterceptorInterface
{
    public function beforeMethodBody(ProxyInterface $proxy, object $object, string $methodName, array $methodArgs): BodyInterceptionResult;
}
