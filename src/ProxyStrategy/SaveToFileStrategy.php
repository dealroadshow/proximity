<?php

namespace Dealroadshow\Proximity\ProxyStrategy;

use Dealroadshow\Proximity\GeneratedProxy;

readonly class SaveToFileStrategy implements ProxyStrategyInterface
{
    public function __construct(private string $proxyClassesDirectory)
    {
    }

    public function applyProxy(GeneratedProxy $proxy): void
    {
        $relativePath = str_replace('\\', '/', $proxy->originalClass->getNamespaceName());
        $dir = $this->proxyClassesDirectory.'/'.$relativePath;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $code = '<?php'.PHP_EOL.$proxy->code;
        $fullPath = $dir.'/'.$proxy->originalClass->getShortName().'.php';
        file_put_contents($fullPath, $code);

        require_once($fullPath);
    }
}
