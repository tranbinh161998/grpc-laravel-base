<?php

namespace binhtv\GrpcLaravel\Server;

use binhtv\GrpcLaravel\Server\Contracts\Kernel as KernelContract;
use binhtv\GrpcLaravel\Server\Contracts\ServiceInvoker;
use Google\Protobuf\Any;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use ReflectionException;
use Spiral\GRPC\Context;
use Spiral\GRPC\Exception\GRPCException;
use Spiral\GRPC\Exception\NotFoundException;
use Spiral\GRPC\StatusCode;
use Spiral\RoadRunner\Worker;
use Throwable;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     * @var Application
     */
    protected Application $app;

    /**
     * Service invoker.
     * @var ServiceInvoker
     */
    protected ServiceInvoker $invoker;

    /**
     * Controllers definition.
     * @var array
     */
    protected array $services = [];

    /**
     * The bootstrap classes for the application.
     * @var array
     */
    protected array $bootstrappers = [
        LoadEnvironmentVariables::class,
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterFacades::class,
        SetRequestForConsole::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    /**
     * Create a new GRPC kernel instance.
     * @param  Application $app
     * @param  ServiceInvoker $invoker
     */
    public function __construct(Application $app, ServiceInvoker $invoker)
    {
        $this->app = $app;
        $this->invoker = $invoker;
    }

    /**
     * Register available services.
     * @param  string $interface
     * @return self
     * @throws ReflectionException
     */
    public function registerService(string $interface): KernelContract
    {
        $service = new ReflectionServiceWrapper($this->invoker, $interface);
        $this->services[$service->getName()] = $service;

        return $this;
    }

     /**
      * Serve GRPC server.
      * @param  Worker $worker
      * @param  callable|null $finalize
      * @return  void
      */
    public function serve(Worker $worker, ?callable $finalize = null): void
    {
        $this->bootstrap();

        while (true) {
            $body = $worker->receive($ctx);

            if (empty($body) && empty($ctx)) {
                return;
            }

            try {
                $ctx = json_decode($ctx, true);
                $resp = $this->invoke(
                    $ctx['service'],
                    $ctx['method'],
                    $ctx['context'] ?? [],
                    $body
                );

                $worker->send($resp);
            } catch (GRPCException $e) {
                $worker->error($this->packError($e));
            } catch (Throwable $e) {
                $worker->error((string) $e);
            } finally {
                if ($finalize !== null) {
                    call_user_func($finalize, $e ?? null);
                }
            }
        }
    }

    /**
     * Bootstrap the application for HTTP requests.
     * @return void
     */
    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    /**
     * Get the Laravel application instance.
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Invoke service method with binary payload and return the response.
     * @param string $service
     * @param string $method
     * @param array  $context
     * @param string $body
     * @return string
     * @throws GRPCException
     * @throws Throwable
     */
    protected function invoke(
        string $service,
        string $method,
        array $context,
        ?string $body
    ): string {
        if (!isset($this->services[$service])) {
            throw new NotFoundException("Service `{$service}` not found.", StatusCode::NOT_FOUND);
        }

        return $this->services[$service]->invoke($method, new Context($context ?? []), $body);
    }

    /**
     * Get the bootstrap classes for the application.
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Packs exception message and code into one string.
     * Internal agreement:
     * Details will be sent as serialized google.protobuf.Any messages after code and exception message separated with |:| delimeter.
     * @param GRPCException $e
     * @return string
     */
    protected function packError(GRPCException $e): string
    {
        $data = [$e->getCode(), $e->getMessage()];

        foreach ($e->getDetails() as $detail) {
            $anyMessage = new Any();

            $anyMessage->pack($detail);

            $data[] = $anyMessage->serializeToString();
        }

        return implode('|:|', $data);
    }
}
