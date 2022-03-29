<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://sun-asterisk.vn/wp-content/uploads/2020/10/logo-sun@2x.png" width="400"></a></p>

## About package
This is a product of programmer sun*.

This package has some class and method help yan can implement Grpc to Laravel easier. So improve system performance thanks to Grpc

## How to use

```shell
composer require binhtv/grpc-base-laravel
```
then run command to publish config.

```shell
php artisan vendor:publish --tag=binhtv-grpc-config
```

You need know how to compile files proto with php


### Client
- Autoload file proto generated:
```shell
"autoload": {
        "psr-4": {
        ...
            "": "protos/generated/"
        },
        ...
    },
```
- Quick call to server (recommend using) <br> <br>
    step 1: create class extend BaseGrpcApi;
```php
<?php

namespace App\Services\MicroserviceGrpc;

use binhtv\GrpcLaravel\Client\Contracts\BaseGrpcApi;
use Google\Protobuf\Internal\Message;
use Illuminate\Http\Request;
use Protobuf\Company\ExampleServiceClient;

/**
 * @method Message ExampleMethod(array|Request $request)
 */

class ExampleGrpcClient extends BaseGrpcApi
{
    public function grpcClient(): string
    {
        return ExampleServiceClient::class;
    }
}
```

step 2: call method in that class;

```php
<?php
 ...
// ExampleMethod is a method of ExampleServiceClient;
 (new ExampleGrpcClient())->ExampleMethod($request);
 ...
```
- Or create new client Grpc
```php
$clientGrpc = (new GrpcFactory)->make(ExampleServiceClient::class);
```
- Use traits;
```php
 ...
 use binhtv\GrpcLaravel\Client\Traits\HandleDataRequest;
 ...
 class ExampleController extends Controller
 {
    use HandleDataRequest;
 }
```

- methods:
   
| Name | Params | Return | Description
| --- | --- | --- | --- |
| `mergeRequestToGrpcRequest` | - An object instance of Illuminate\Http\Request; <br/> - Path of GRPC request class| object: grpc request | Convert laravel request to grpc request
| `dataGrpc` | - An object instance of request or response GRPC  | Array: Array have a key is 'data' | Create a array have key is 'data' and value is data grpc
| `prepareDataRequestFromGrpc` | - An object instance of  request or response GRPC | object: instance of Illuminate\Http\Request | Convert grpc request to laravel request
| `removeLinkPagination` | - An array | array | Remove key 'links' and 'path' in array
### Serve
- Start serve:
```shell
./vendor/binhtv/grpc-base-laravel/rr-grpc serve -v -d
```

- Example worker file:
```php
<?php

declare(strict_types=1);

use App\Grpc\ExampleGrpcController;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\Worker;

ini_set('display_errors', 'stderr');

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->singleton(
    binhtv\GrpcLaravel\Server\Contracts\Kernel::class,
    binhtv\GrpcLaravel\Server\Kernel::class
);

$app->singleton(
    binhtv\GrpcLaravel\Server\Contracts\ServiceInvoker::class,
    binhtv\GrpcLaravel\Server\LaravelServiceInvoker::class
);

$kernel = $app->make(binhtv\GrpcLaravel\Server\Kernel::class);

$kernel->registerService(ExampleGrpcController::class);

$w = new Worker(new StreamRelay(STDIN, STDOUT));

$kernel->serve($w);

```
