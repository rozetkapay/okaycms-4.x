<?php

declare(strict_types=1);

namespace Okay\Modules\RozetkaPay\RozetkaPay\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Entities\OrdersEntity;
use Okay\Core\EntityFactory;
use Okay\Modules\RozetkaPay\RozetkaPay\Models\Gateway\Refund;

class RefundAdmin extends IndexAdmin
{
    public function fetch()
    {
        $this->response->redirectTo($_SERVER["HTTP_REFERER"]);
    }

    public function execute(Refund $refund, EntityFactory $entityFactory)
    {
        if(isset($_GET['order'])) {
            $order = $this->getOrder($_GET['order']);
            $refundResult = $refund->refund($order);
            $ordersEntity = $this->entityFactory->get(OrdersEntity::class);
            if(isset($refundResult->is_success) && $refundResult->is_success){
                $ordersEntity->update((int) $_GET['order'], ['payment_details' => $refundResult]);
                if ($order->paid === 1) {
                    $ordersEntity->update((int) $_GET['order'], ['paid' => 0]);
                }
            }
        }
        $this->fetch();
    }

    private function getOrder($orderId)
    {
        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $this->entityFactory->get(OrdersEntity::class);
        $order = $ordersEntity->findOne(['id' => $orderId]);
        return $order;
    }
}