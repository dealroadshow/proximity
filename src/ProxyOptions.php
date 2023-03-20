<?php

namespace Dealroadshow\Proximity;

use Dealroadshow\Proximity\MethodsInterception\BodyInterceptorInterface;
use Dealroadshow\Proximity\MethodsInterception\ResultInterceptorInterface;

readonly class ProxyOptions
{
    /**
     * @param BodyInterceptorInterface[][] $bodyInterceptors
     * @param ResultInterceptorInterface[][] $resultInterceptors
     * @param BodyInterceptorInterface[] $defaultBodyInterceptors
     * @param ResultInterceptorInterface[] $defaultResultInterceptors
     */
    public function __construct(
        public array $bodyInterceptors = [],
        public array $resultInterceptors = [],
        public array $defaultBodyInterceptors = [],
        public array $defaultResultInterceptors = []
    ) {
    }

    public function bodyInterceptorsForMethod(string $methodName): array
    {
        return $this->bodyInterceptors[$methodName] ?? $this->defaultBodyInterceptors;
    }

    public function resultInterceptorsForMethod(string $methodName): array
    {
        return $this->resultInterceptors[$methodName] ?? $this->defaultResultInterceptors;
    }
}
