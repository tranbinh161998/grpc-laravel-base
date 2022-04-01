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

    /**
     * @param array $data
     * @return array
     */
    public function removeLinkPagination(array $data)
    {
        if (key_exists('links', $data)) {
            unset($data['links']);
        }

        if (key_exists('meta', $data)) {
            unset($data['meta']['links']);
            unset($data['meta']['path']);
        }

        return $data;
    }

    /**
     * @param Message $requestGrpc
     * @return Request
     */
    public function prepareDataRequestFromGrpc(Message $requestGrpc)
    {
        $requestLaravel = new Request();

        $requestLaravel->merge(json_decode($requestGrpc->serializeToJsonString(), true));

        app('request')['page'] = $requestLaravel->page ?? 1;

        return $requestLaravel;
    }
}
