<?php

namespace Okay\Modules\RozetkaPay\RozetkaPay;

return [
    'RozetkaPay_callback' => [
        'slug' => 'payment/rozetkapay/callback',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\CallbackController',
            'method' => 'payOrder',
        ],
    ],
];