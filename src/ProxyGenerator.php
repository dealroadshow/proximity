<?php

namespace Dealroadshow\Proximity;

use Dealroadshow\Proximity\MethodsInterception\InterceptionContext;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;

readonly class ProxyGenerator
{
    public const DEFAULT_NAMESPACE = 'Dealroadshow\Proximity\Proxy';

    private Printer $printer;

    public function __construct(public string $proxyClassesNamespace = self::DEFAULT_NAMESPACE)
    {
        $this->printer = new Printer();
    }

    public function generate(\ReflectionClass $reflectionClass): GeneratedProxy
    {
        $this->ensureClassCanBeProxied($reflectionClass);

        $namespaceName = $this->proxyClassesNamespace;
        if ($reflectionClass->getNamespaceName()) {
            $namespaceName .= '\\'.$reflectionClass->getNamespaceName();
        }
        $namespace = new PhpNamespace($namespaceName);
        $namespace
            ->addUse(InterceptionContext::class)
            ->addUse(ProxyOptions::class);

        $className = $reflectionClass->getName().'Proxy';
        $class = new ClassType($className, $namespace);
        $namespace->add($class);

        $class
            ->setExtends($reflectionClass->getName())
            ->addImplement(ProxyInterface::class)
            ->setReadOnly($reflectionClass->isReadOnly());

        $this->generateProperties($reflectionClass, $class);
        $this->generateConstructor($reflectionClass, $class);
        $this->generateMethods($reflectionClass, $class);

        $file = new PhpFile();
        $file->addNamespace($namespace);

        $fqcn = $namespace->getName().'\\'.$class->getName();
        $code = $this->printer->printFile($file);

        return new GeneratedProxy($fqcn, $code);
    }

    private function generateMethods(\ReflectionClass $reflectionClass, ClassType $class): void
    {
        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->isConstructor() || $method->isPrivate() || $method->isFinal() || $method->isStatic()) {
                continue;
            }

            $this->generateMethod($method, $class);
        }
    }

    private function generateMethod(\ReflectionMethod $reflectionMethod, ClassType $class): void
    {
        $method = $class->addMethod($reflectionMethod->getName());
        $method
            ->setReturnReference($reflectionMethod->returnsReference())
            ->setVariadic($reflectionMethod->isVariadic());

        if ($reflectionMethod->hasReturnType()) {
            $method->setReturnType((string)$reflectionMethod->getReturnType());
        }

        foreach ($reflectionMethod->getParameters() as $reflectionParam) {
            $param = $method
                ->addParameter($reflectionParam->name)
                ->setReference($reflectionParam->isPassedByReference())
                ->setNullable($reflectionParam->allowsNull());
            if ($reflectionParam->hasType()) {
                $param->setType((string)$reflectionParam->getType());
            }
            if ($reflectionParam->isVariadic()) {
                $method->setVariadic();
            }
            if ($reflectionParam->isDefaultValueAvailable()) {
                $param->setDefaultValue($reflectionParam->getDefaultValue());
            }
        }

        static $codeTemplate = <<<'CODE'
        $methodName = '{METHOD}';
        $args = func_get_args();
        foreach ($this->proximityOptions->bodyInterceptorsForMethod($methodName) as $interceptor) {
            $result = $interceptor->beforeMethodBody($this, $this->proximityOriginalObject, $methodName, $args);
            if ($result->preventMethodBody) {
                {RETURN_STATEMENT_BEFORE_BODY}
            }
        }
        
        $returnValue = parent::{METHOD}(...$args);
        
        $context = new InterceptionContext($returnValue);
        foreach ($this->proximityOptions->resultInterceptorsForMethod($methodName) as $interceptor) {
            $interceptor->afterMethodBody($this, $this->proximityOriginalObject, $methodName, $args, $context);
        }
        
        {RETURN_STATEMENT_AFTER_BODY}
        CODE;

        $isVoid = 'void' === (string)$reflectionMethod->getReturnType();
        $returnStatementBeforeBody = $isVoid ? 'return;' : 'return $result->returnValue;';
        $returnStatementAfterBody = $isVoid ? 'return;' : 'return $context->returnValue;';

        $code = strtr($codeTemplate, [
            '{METHOD}' => $reflectionMethod->getName(),
            '{RETURN_STATEMENT_BEFORE_BODY}' => $returnStatementBeforeBody,
            '{RETURN_STATEMENT_AFTER_BODY}' => $returnStatementAfterBody,
        ]);

        $method->setBody($code);
    }

    private function generateConstructor(\ReflectionClass $reflectionClass, ClassType $class): void
    {
        $method = $class->addMethod('__construct');
        $method
            ->addParameter('object')
            ->setType($reflectionClass->getName());
        $method
            ->addParameter('options')
            ->setType(ProxyOptions::class);

        $code = <<<'CODE'
        if (null === self::$proximityReflectionClass) {
            self::$proximityReflectionClass = new \ReflectionClass($this::class);
        }
        $class = self::$proximityReflectionClass;
        
        $this->proximityOriginalObject = $object;
        $this->proximityOptions = $options;
        
        $accessorClosure = function &(string $propName) {
            return $this->$propName;
        };
        $readonlyAccessorClosure = function (string $propName) {
            return $this->$propName;
        };
        CODE;

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isReadOnly()) {
                if ($property->isPrivate()) {
                    $propertyInitializationCode = <<<'PROPERTY_CODE'
                    $this->{PROPERTY} = $readonlyAccessorClosure->call($object, '{PROPERTY}');
                    PROPERTY_CODE;
                    $propertyInitializationCode = strtr($propertyInitializationCode, ['{PROPERTY}' => $property->name]);
                    $code = $code.PHP_EOL.$propertyInitializationCode;

                    continue;
                }

                $code = $code.PHP_EOL.strtr('$this->{PROPERTY} = $object->{PROPERTY};', ['{PROPERTY}' => $property->name]);

                continue;
            }

            if ($property->isPrivate()) {
                $propertyInitializationCode = <<<'PROPERTY_CODE'
                $this->{PROPERTY} = &$accessorClosure->bindTo($object, '{CLASS}')->__invoke('{PROPERTY}');
                PROPERTY_CODE;
                $propertyInitializationCode = strtr($propertyInitializationCode, [
                    '{PROPERTY}' => $property->name,
                    '{CLASS}' => $reflectionClass->getName(),
                ]);
                $code = $code.PHP_EOL.$propertyInitializationCode;

                continue;
            }

            $code = $code.PHP_EOL.strtr('$this->{PROPERTY} = &$object->{PROPERTY};', ['{PROPERTY}' => $property->name]);
        }

        $method->setBody($code);
    }

    private function generateProperties(\ReflectionClass $reflectionClass, ClassType $class): void
    {
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $this->generateProperty($class, $reflectionProperty);
        }

        $class
            ->addProperty('proximityReflectionClass')
            ->setStatic()
            ->setPrivate()
            ->setType(\ReflectionClass::class.'|null')
            ->setValue(null);

        $class
            ->addProperty('proximityOriginalObject')
            ->setPrivate()
            ->setType($reflectionClass->getName())
            ->setReadOnly();

        $class
            ->addProperty('proximityOptions')
            ->setPrivate()
            ->setType(ProxyOptions::class)
            ->setReadOnly();
    }

    private function generateProperty(ClassType $class, \ReflectionProperty $reflectionProperty): void
    {
        $property = $class
            ->addProperty($reflectionProperty->name)
            ->setReadOnly($reflectionProperty->isReadOnly())
            ->setStatic($reflectionProperty->isStatic());
        if ($reflectionProperty->hasType()) {
            $property->setType((string)$reflectionProperty->getType());
        }

        if ($reflectionProperty->isPublic()) {
            $property->setPublic();
        } elseif ($reflectionProperty->isProtected()) {
            $property->setProtected();
        } elseif ($property->isPrivate()) {
            $property->setPrivate();
        }
    }

    private function ensureClassCanBeProxied(\ReflectionClass $class): void
    {
        if ($class->isFinal()) {
            throw new \InvalidArgumentException(
                sprintf('Class "%s" cannot be proxied because it is final', $class->getName())
            );
        }

        if ($class->getMethod('__construct')?->isPrivate()) {
            throw new \InvalidArgumentException(
                sprintf('Class "%s" cannot be proxied because it has private constructor', $class->getName())
            );
        }
    }
}
