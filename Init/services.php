<?php


namespace Okay\Modules\RozetkaPay\RozetkaPay;


use Okay\Core\EntityFactory;
use Okay\Entities\OrdersEntity;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Modules\RozetkaPay\RozetkaPay\Models\Gateway\CreatePayment;
use Okay\Modules\RozetkaPay\RozetkaPay\Models\Gateway\Client\HttpCurl;
use Okay\Modules\RozetkaPay\RozetkaPay\Models\Gateway\Refund;
use Okay\Modules\RozetkaPay\RozetkaPay\Backend\Controllers\RefundAdmin;
use Okay\Core\QueryFactory;
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

return [
    PaymentForm::class => [
        'class' => PaymentForm::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(Languages::class),
            new SR(Money::class),
            new SR(CreatePayment::class),
            new SR(QueryFactory::class),
        ],
    ],
    CreatePayment::class => [
        'class' => CreatePayment::class,
        'arguments' => [
            new SR(HttpCurl::class),
            new SR(OrdersEntity::class),
        ],
    ],
    HttpCurl::class => [
        'class' => HttpCurl::class,
        'arguments' => [
        ],
    ],
    OrdersEntity::class => [
        'class' => OrdersEntity::class,
        'arguments' => [
        ],
    ],
    RefundAdmin::class => [
        'class' => RefundAdmin::class,
        'arguments' => [
            new SR(Refund::class),
        ],
    ],
    Refund::class => [
        'class' => Refund::class,
        'arguments' => [
            new SR(HttpCurl::class),
            new SR(EntityFactory::class),
            new SR(QueryFactory::class)
        ],
    ],
];