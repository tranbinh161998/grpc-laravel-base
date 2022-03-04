<?php

namespace binhtv\GrpcLaravel\Client\Traits;

use Google\Protobuf\Internal\Message;
use Illuminate\Http\Request;

trait HandleDataRequest
{
    public function dataGrpc($dataGrpc)
    {
        if ($dataGrpc instanceof Message) {
            return ['data' => json_decode($dataGrpc->serializeToJsonString(), true)];
        }

        return $dataGrpc;
    }

    /**
     * Create new request grpc with params of request laravel
     * @param Request $request
     * @param string $class
     * @return mixed
     */
    public function mergeRequestToGrpcRequest(Request $request, string $class)
    {
        $requestGrpc = new $class();

        if (empty($request->all())) {
            return $requestGrpc;
        }

        $requestGrpc->mergeFromJsonString(json_encode($request->all()));

        return $requestGrpc;
    }
}
