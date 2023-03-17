<?php

namespace Dealroadshow\Proximity\MethodsInterception;

class InterceptionContext
{
    public function __construct(public mixed $returnValue = null)
    {
    }
}
