<?php

namespace binhtv\GrpcLaravel\Client;

class GrpcLaravelBaseServiceProvider extends \Illuminate\Support\ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/grpc.php' => config_path('grpc.php'),
        ], 'binhtv-grpc-config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
