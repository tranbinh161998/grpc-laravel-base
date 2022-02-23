<?php

namespace CANHNV\SimpleAdmin;

use Illuminate\Support\ServiceProvider;

class GrpcLaravelBaseServiceProvider extends ServiceProvide {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/grpc.php' => config_path('./config/grpc.php'),
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
