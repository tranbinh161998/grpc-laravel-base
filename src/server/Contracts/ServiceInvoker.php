<?php

namespace binhtv\GrpcLaravel\Server\Contracts;

use Spiral\GRPC\Method;
use Spiral\GRPC\ContextInterface;
use Illuminate\Contracts\Foundation\Application;

interface ServiceInvoker
{
    /**
     * Invoke service.
     *
     * @param  string $interface
     * @param  Method $method
     * @param  ContextInterface $context
     * @param  string $input
     *
     * @return  string
     */
    public function invoke(
        string $interface,
        Method $method,
        ContextInterface $context,
        ?string $input
    ): string;

    /**
     * Get the Laravel application instance.
     *
     * @return Application
     */
    public function getApplication(): Application;
}
