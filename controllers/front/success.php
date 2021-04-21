<?php
/**
 * UniversalPay
 *
 * @author    UniversalPay
 * @copyright Copyright (c) 2018 UniversalPay
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 */

class UniversalPaySuccessModuleFrontController extends ModuleFrontController
{

    private $order;
    private $universalpay;

    public function initContent()
    {
        parent::initContent();
        $universalpay = new UniversalPay();
        $this->universalpay = $universalpay;
        $id_order = Tools::getValue('order');
        $merchantId = Tools::getValue('merchantTxId');

        if(!$id_order){
            $id_order = $universalpay->getIdOrderByToken($merchantId)['0']['id_order'];
        }
        $orderPayment = $universalpay->getOrdersByIdOrder($id_order);

        if (!$orderPayment) {
            Tools::redirect('index.php?controller=history', __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
        }

        $universalpay->id_order = $orderPayment['0']['id_order'];
        $universalpay->id_cart = $orderPayment['0']['id_cart'];
        $universalpay->token = $orderPayment['0']['token'];

        $universalpay->updateOrder();

        $this->order = new Order($universalpay->id_order);
        $currentState = $this->order->getCurrentStateFull($this->context->language->id);

        $evoStatus =  $orderPayment['0']['status'];
        $statusDesc = $this->universalpay->getStatusDescByEvoStatus($evoStatus,  $this->context->language->iso_code);
        
        $this->context->smarty->assign([
            'evoLogo' => $universalpay->getEVOLogo(),
            'orderPublicId' => $this->order->getUniqReference(),
            'redirectUrl' => $this->getRedirectLink($universalpay->id_cart, $universalpay->id_order),
            'orderStatus' => $statusDesc,
            'orderEvoStatus' => $evoStatus,
            'orderId' => $universalpay->id_order,
            'token' => $universalpay->token,
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
            'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn()
        ]);

        $this->setTemplate($universalpay->buildTemplatePath('success'));
    }

    
    
    
    private function getRedirectLink($id_cart, $id_order)
    {
        if (Cart::isGuestCartByCartId($id_cart)) {
            $customer = new Customer((int)$this->order->id_customer);

            return $this->context->link->getPageLink(
                'guest-tracking',
                null,
                $this->context->language->id,
                ['id_order' => $this->order->reference, 'email' => urlencode($customer->email)]
            );
        }

        return $this->context->link->getPageLink(
            'order-detail',
            null,
            $this->context->language->id,
            ['id_order' => $id_order]
        );
    }

    /**
     * Execute the hook displayPaymentReturn
     */
    private function displayPaymentReturn()
    {
        $params = $this->displayHook();
        if ($params && is_array($params)) {
            return Hook::exec('displayPaymentReturn', $params, $this->module->id);
        }
        return false;
    }

    /**
     * Execute the hook displayOrderConfirmation
     */
    private function displayOrderConfirmation()
    {
        $params = $this->displayHook();
        if ($params && is_array($params)) {
            return Hook::exec('displayOrderConfirmation', $params);
        }
        return false;
    }

    private function displayHook()
    {
        if (Validate::isLoadedObject($this->order)) {
            $currency = new Currency((int) $this->order->id_currency);
            $params = [];
            $params['objOrder'] = $this->order;
            $params['currencyObj'] = $currency;
            $params['currency'] = $currency->sign;
            $params['total_to_pay'] = $this->order->getOrdersTotalPaid();

            return $params;
        }
        return false;
    }
}