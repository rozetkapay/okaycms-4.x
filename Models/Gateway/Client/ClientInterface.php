<?php

namespace Okay\Modules\RozetkaPay\RozetkaPay\Models\Gateway\Client;

interface ClientInterface
{
    /**
     * @param $method
     * @param $url
     * @param $params
     * @param $config
     * @return mixed
     */
    public function request($method, $url, $params, $config);
}