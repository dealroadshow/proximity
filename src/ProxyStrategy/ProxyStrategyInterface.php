<?php

namespace Dealroadshow\Proximity\ProxyStrategy;
use Dealroadshow\Proximity\GeneratedProxy;

interface ProxyStrategyInterface
{
    public function apply(GeneratedProxy $proxy): void;
}
