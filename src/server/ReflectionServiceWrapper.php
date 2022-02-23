<?php

namespace binhtv\GrpcLaravel\Server;

use binhtv\GrpcLaravel\Server\Contracts\ServiceInvoker;
use binhtv\GrpcLaravel\Server\Contracts\ServiceWrapper;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Spiral\GRPC\Method;
use Spiral\GRPC\ContextInterface;
use Spiral\GRPC\Exception\ServiceException;
use Spiral\GRPC\Exception\NotFoundException;
use Spiral\GRPC\StatusCode;

class ReflectionServiceWrapper implements ServiceWrapper
{
    /**
     * Service name.
     * @var string
     */
    protected string $name;

    /**
     * Service's methods
     * @var array
     */
    protected array $methods = [];

    /**
     * Invoker.
     * @var ServiceInvoker
     */
    protected ServiceInvoker $invoker;

    /**
     * Fully qualified service interface.
     * @var string
     */
    protected string $interface;

    /**
     * Create new ServiceWrapper instance.
     * @param  ServiceInvoker $invoker
     * @param  string $interface
     * @throws ReflectionException
     */
    public function __construct(
        ServiceInvoker $invoker,
        string $interface
    ) {
        $this->invoker = $invoker;
        $this->interface = $interface;

        $this->configure($interface);
    }

    /**
     * Retrieve service name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieve public methods.
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Invoke service.
     * @param  string $method
     * @param  ContextInterface $context
     * @param  string|null $input
     * @return string
     */
    public function invoke(string $method, ContextInterface $context, ?string $input): string
    {
        if (!isset($this->methods[$method])) {
            throw new NotFoundException("Method `{$method}` not found in service `{$this->name}`.");
        }

        return $this->invoker->invoke($this->interface, $this->methods[$method], $context, $input);
    }

    /**
     * Configure service name and methods.
     * @param  string $interface
     * @throws \Spiral\Grpc\Exception\ServiceException
     * @throws ReflectionException
     */
    protected function configure(string $interface)
    {
        try {
            $r = new ReflectionClass($interface);
            if (!$r->hasConstant('NAME')) {
                throw new ServiceException(
                    "Invalid service interface `{$interface}`, constant `NAME` not found."
                );
            }
            $this->name = $r->getConstant('NAME');
        } catch (\ReflectionException $e) {
            throw new ServiceException(
                "Invalid service interface `{$interface}`.",
                StatusCode::INTERNAL,
                $e
            );
        }

        $this->methods = $this->fetchMethods($interface);
    }

    /**
     * @param  string $interface
     * @return array
     * @throws ReflectionException
     */
    protected function fetchMethods(string $interface): array
    {
        $reflection = new ReflectionClass($interface);

        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (Method::match($method)) {
                $methods[$method->getName()] = Method::parse($method);
            }
        }

        return $methods;
    }
}
