<?php

namespace binhtv\GrpcLaravel\Client\Contracts;

use binhtv\GrpcLaravel\Client\GrpcFactory;
use binhtv\GrpcLaravel\Client\LaravelHandleError;
use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionClass;

abstract class BaseGrpcApi
{
    protected $grpcRequest;

    protected $grpcFactory;

    protected $errorHandler;

    abstract public function grpcClient(): string;

    public function __construct()
    {
        $config = app()->get(ConfigRepository::class);
        $this->grpcFactory = new GrpcFactory($config);
        $this->errorHandler = new LaravelHandleError();
    }

    /**
     * Convert data to grpc request
     * @param $request
     * @return mixed
     * @throws Exception
     */
    protected function convertRequestToGrpcRequest($request)
    {
        if (empty($request)) {
            return $this->grpcRequest;
        }

        $propertiesOfGrpcRequest = $this->getPropertyOfRequestClass(get_class($this->grpcRequest));

        if (is_array($request)) {

            $paramsPass = array_filter($request, function($k) use ($propertiesOfGrpcRequest) {
                return in_array($k, $propertiesOfGrpcRequest);
            }, ARRAY_FILTER_USE_KEY);

            if (empty($paramsPass)) {
                return $this->grpcRequest;
            }

            return $this->grpcRequest->mergeFromJsonString(json_encode($paramsPass));
        }

        if ($request instanceof Request) {
            if (empty($request->only($propertiesOfGrpcRequest))) {
                return $this->grpcRequest;
            }

            return $this->grpcRequest->mergeFromJsonString(json_encode($request->only($propertiesOfGrpcRequest)));
        }

        return $this->grpcRequest;
    }

    /**
     * Auto get request grpc and create object
     * @param $methodName
     * @throws Exception
     */
    protected function createInstanceOfRequest($methodName)
    {
        try {
            $reflectionMethod = new ReflectionMethod($this->grpcClient(), $methodName);

            $param = $reflectionMethod->getParameters();
            if (count($param) <= 0) {
                throw new Exception("Undefined method $methodName");
            }

            $typeString = $param[0]->getType()->getName();
            $this->grpcRequest = new $typeString();
        } catch (\ReflectionException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @throws Exception
     */
    protected function convertMultiParamsToGrpcRequest(array $params)
    {
        foreach ($params as $key => $value) {
            $this->convertRequestToGrpcRequest($value);
        }
    }

    /**
     * Get list property of class grpc request
     * @param string $class
     * @return array
     * @throws Exception
     */
    public function getPropertyOfRequestClass(string $class)
    {
        $properties = [];
        try {
            $property = new ReflectionClass($class);
            foreach ($property->getProperties() as $key => $value) {
                array_push($properties, $value->getName());
            }

            return $properties;
        } catch (\ReflectionException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $methodName
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function __call($methodName, $params)
    {
        try {
            $this->createInstanceOfRequest($methodName);

            $this->convertMultiParamsToGrpcRequest($params);

            $client = $this->grpcFactory->make($this->grpcClient());

            [$response, $status] = $client->$methodName($this->grpcRequest)->wait();

            $this->errorHandler->handle($status, $status->code);

            return json_decode($response->serializeToJsonString(), true);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
