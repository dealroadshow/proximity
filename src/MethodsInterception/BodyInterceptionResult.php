<?php

namespace Dealroadshow\Proximity\MethodsInterception;

readonly class BodyInterceptionResult
{
    public function __construct(public bool $preventMethodBody = false, public mixed $returnValue = null)
    {
        if (!$this->preventMethodBody && null !== $this->returnValue) {
            throw new \LogicException('$returnValue cannot be specified when $preventMethodBody is false');
        }
    }
}
