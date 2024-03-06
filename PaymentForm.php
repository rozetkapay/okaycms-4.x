<?php


namespace Okay\Modules\RozetkaPay\RozetkaPay;


use Okay\Core\EntityFactory;
use Okay\Core\Modules\AbstractModule;
use Okay\Core\Modules\Interfaces\PaymentFormInterface;
use Okay\Core\Money;
use Okay\Core\Router;
use Okay\Core\Languages;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Entities\PurchasesEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Modules\RozetkaPay\RozetkaPay\Models\Gateway\CreatePayment;
use Okay\Core\QueryFactory;

class PaymentForm extends AbstractModule implements PaymentFormInterface
{

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var Languages
     */
    private $languages;

    /**
     * @var Money
     */
    private $money;

    /**
     * @var CreatePayment
     */
    private $createPayment;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @param EntityFactory $entityFactory
     * @param Languages $languages
     * @param Money $money
     * @param CreatePayment $createPayment
     * @param QueryFactory $queryFactory
     */
    public function __construct(
        EntityFactory $entityFactory,
        Languages $languages,
        Money $money,
        CreatePayment $createPayment,
        QueryFactory $queryFactory
    )
    {
        parent::__construct();
        $this->entityFactory = $entityFactory;
        $this->languages     = $languages;
        $this->money         = $money;
        $this->createPayment = $createPayment;
        $this->queryFactory  = $queryFactory;
    }

    /**
     * @inheritDoc
     */
    public function checkoutForm($orderId)
    {
        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $this->entityFactory->get(OrdersEntity::class);

        /** @var PurchasesEntity $purchasesEntity */
        $purchasesEntity = $this->entityFactory->get(PurchasesEntity::class);

        /** @var PaymentsEntity $paymentsEntity */
        $paymentsEntity = $this->entityFactory->get(PaymentsEntity::class);

        /** @var CurrenciesEntity $currenciesEntity */
        $currenciesEntity = $this->entityFactory->get(CurrenciesEntity::class);

        /** @var LanguagesEntity $languagesEntity */
        $languagesEntity = $this->entityFactory->get(LanguagesEntity::class);

        $order = $ordersEntity->get((int)$orderId);

        $purchases = $purchasesEntity->find(['order_id' => (int)$orderId]);
        $paymentMethod = $paymentsEntity->get($order->payment_method_id);
        $createDetails = $this->getPaymentDetails((int)$orderId, $this->queryFactory, OrdersEntity::getTable());
        if(empty($createDetails)) {
            $settings = $paymentsEntity->getPaymentSettings($paymentMethod->id);
            $price           = $this->money->convert($order->total_price, $paymentMethod->currency_id, false, false, 2);
            $paymentCurrency = $currenciesEntity->get(intval($paymentMethod->currency_id));
            $orderArray = (array)$order;
            $orderArray['currency'] = (array)$paymentCurrency;
            $orderArray['callback_url'] = Router::generateUrl('RozetkaPay_callback', [], true);
            $orderArray['result_url'] = Router::generateUrl('order', ['url' => $order->url], true);
            $orderArray['settings'] = $settings;
            $apiResult = $this->createPayment->createPayment($orderArray);
            $details = json_encode($apiResult);
            $ordersEntity->update((int)$order->id, ['payment_details' => $details]);
        } else {
            $apiResult = $createDetails;
        }

        $this->design->assign('rozetkaPayUrl', $apiResult->action->value);
        list($firstName, $lastName) = $this->separateFullNameOnFirstNameAndLastName($order->name);

        return $this->design->fetch('form.tpl');
    }

    private function separateFullNameOnFirstNameAndLastName($fullName)
    {
        $parts = explode(' ', $fullName);
        $firstName = isset($parts[0]) ? $parts[0] : '';
        $lastName  = isset($parts[1]) ? $parts[1] : '';
        return [$firstName, $lastName];
    }

    private function getPurchaseNames($purchases)
    {
        $purchasesNames = [];

        foreach($purchases as $purchase) {
            $purchasesNames[] = $purchase->product_name.' '.$purchase->variant_name;
        }

        return $purchasesNames;
    }

    private function getPurchasePrices($purchases, $currencyId)
    {
        $purchasesPrices = [];

        foreach($purchases as $purchase) {
            $purchasesPrices[] = $this->money->convert($purchase->price, $currencyId, false, false, 2);
        }

        return $purchasesPrices;
    }

    private function getPurchaseCount($purchases)
    {
        $purchasesCount = [];

        foreach($purchases as $purchase) {
            $purchasesCount[] = $purchase->amount;
        }

        return $purchasesCount;
    }

    private function generateHash($settings)
    {
        $keysForSignature = [
            'merchantAccount',
            'merchantDomainName',
            'orderReference',
            'orderDate',
            'amount',
            'currency',
            'productName',
            'productCount',
            'productPrice'
        ];

        $hash = [];
        foreach ($keysForSignature as $dataKey) {
            $variableDataKey = $this->design->getVar($dataKey);
            if (empty($variableDataKey)) {
                continue;
            }

            if (is_array($variableDataKey)) {
                foreach ($variableDataKey as $v) {
                    $hash[] = $v;
                }
                continue;
            }

            $hash[] = $variableDataKey;
        }
        $hash = implode(';', $hash);
        return hash_hmac('md5', $hash, $settings['rozetkapay_secretkey']);
    }

    private function formatPhone($phone)
    {
        $phone = str_replace(['+', ' ', '(', ')'], ['','','',''], $phone);

        if(strlen($phone) == 10){
            return '38'.$phone;
        }

        if(strlen($phone) == 11){
            return '3'.$phone;
        }

        return $phone;
    }

    private function getPaymentDetails($id, $queryFactory, $table)
    {
        $select = $queryFactory->newSelect();
        $data = $select->from($table)
            ->cols(['payment_details'])
            ->where('id=:id')
            ->bindValue('id', $id)
            ->results('payment_details');

        return json_decode($data[0]);
    }
}