<?php

namespace binhtv\GrpcLaravel\Server;

use binhtv\GrpcLaravel\Server\Contracts\ServiceInvoker;
use Google\Protobuf\Internal\Message;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Spiral\GRPC\ContextInterface;
use Spiral\GRPC\Exception\InvokeException;
use Spiral\GRPC\Method;
use Spiral\GRPC\StatusCode;
use Throwable;

class LaravelServiceInvoker implements ServiceInvoker
{
    /**
     * The application implementation.
     * @var Application
     */
    protected Application $app;

    /**
     * Create new Invoker instance
     * @param  Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritdoc
     * @throws BindingResolutionException
     */
    public function invoke(
        string $interface,
        Method $method,
        ContextInterface $context,
        ?string $input
    ): string {
        $instance = $this->getApplication()->make($interface);
        $out = $instance->{$method->getName()}($context, $this->makeInput($method, $input));

        try {
            return $out->serializeToString();
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), StatusCode::INTERNAL, $e);
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
     * @param  Method $method
     * @param  string|null $body
     * @return Message
     * @throws InvokeException
     */
    private function makeInput(Method $method, ?string $body): Message
    {
        try {
            $class = $method->getInputType();

            /** @var Message $in */
            $in = new $class();
            $in->mergeFromString($body);

            return $in;
        } catch (Throwable $e) {
            throw new InvokeException($e->getMessage(), StatusCode::INTERNAL, $e);
        }
    }
}
