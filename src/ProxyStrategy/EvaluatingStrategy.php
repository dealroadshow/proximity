<?php

namespace Dealroadshow\Proximity\ProxyStrategy;

use Dealroadshow\Proximity\GeneratedProxy;

class EvaluatingStrategy implements ProxyStrategyInterface
{
    public function apply(GeneratedProxy $proxy): void
    {
        eval($proxy->code);
    }
}
