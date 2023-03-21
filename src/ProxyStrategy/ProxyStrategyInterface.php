<?php

namespace Dealroadshow\Proximity\ProxyStrategy;
use Dealroadshow\Proximity\GeneratedProxy;

interface ProxyStrategyInterface
{
    public function applyProxy(GeneratedProxy $proxy): void;
}
