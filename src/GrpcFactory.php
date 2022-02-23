<?php

namespace binhtv\GrpcLaravel\Client;

use Illuminate\Contracts\Config\Repository as Config;

class GrpcFactory
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $client
     * @return mixed
     */
    public function make(string $client)
    {
        $config = $this->config->get("grpc.services.{$client}");

        $credentials = $this->createInsecureCredentials();

        $client = new $client($config['host'].':'.$config['port'], [
            'credentials' => $credentials,
        ]);

        return $client;
    }

    protected function createInsecureCredentials()
    {
        return \Grpc\ChannelCredentials::createInsecure();
    }
}
