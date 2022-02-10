<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use ReflectionException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

use function array_unshift;
use function array_values;
use function call_user_func_array;

/**
 * @interal
 */
final class ControllerAction
{

    private object $controller_instance;
    private string $controller_method;

    /**
     * @param array{0: class-string, 1:string} $class_callable
     *
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function __construct(array $class_callable, ContainerInterface $container)
    {
        [$class, $method] = $class_callable;
        $this->controller_instance = $this->instantiateController($class, $container);
        $this->controller_method = $method;
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    public function execute(Request $request, array $captured_args_decoded)
    {
        $callable = [$this->controller_instance, $this->controller_method];

        if (Reflection::firstParameterType($callable) === Request::class) {
            array_unshift($captured_args_decoded, $request);
        }

        return call_user_func_array($callable, array_values($captured_args_decoded));
    }

    /**
     * @return class-string<MiddlewareInterface>[]
     */
    public function middleware(): array
    {
        if (!$this->controller_instance instanceof AbstractController) {
            return [];
        }

        return $this->controller_instance->getMiddleware($this->controller_method);
    }

    /**
     * @param class-string $class
     *
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    private function instantiateController(string $class, ContainerInterface $container): object
    {
        try {
            /** @var object $instance */
            $instance = $container->get($class);
        } catch (NotFoundExceptionInterface $e) {
            $instance = (new ReflectionClass($class))->newInstance();
        }

        if ($instance instanceof AbstractController) {
            $instance->setContainer($container);
        }
        return $instance;
    }

}