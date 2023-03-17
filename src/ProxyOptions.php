<?php

namespace Dealroadshow\Proximity;

use Dealroadshow\Proximity\MethodsInterception\BodyInterceptorInterface;
use Dealroadshow\Proximity\MethodsInterception\ResultInterceptorInterface;

readonly class ProxyOptions
{
    /**
     * @param BodyInterceptorInterface[][] $bodyInterceptors
     * @param ResultInterceptorInterface[][] $resultInterceptors
     */
    public function __construct(public array $bodyInterceptors, public array $resultInterceptors)
    {
    }

    public function bodyInterceptorsForMethod(string $methodName): array
    {
        return $this->bodyInterceptors[$methodName] ?? [];
    }

    public function resultInterceptorsForMethod(string $methodName): array
    {
        return $this->resultInterceptors[$methodName] ?? [];
    }
}
