<?php

class Ps_Platba360ValidationModuleFrontController extends ModuleFrontController {

    public function postProcess() {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'ps_platba360') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', array(), 'Modules.Platba360.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        
        $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, null, null, (int)$currency->id, false, $customer->secure_key);
        $orderId = Order::getIdByCartId((int)$cart->id);

        $url = $this->generatePaymentLink($orderId, $total);
        
        Tools::redirect($url);
    }

    private function generatePaymentLink($orderId, $total) {
        $url = 'https://platba360.cz';
        $query = $this->prepareQuery($orderId, $total);
        $launchUrl = $url . '?' . $this->signQuery($query, Configuration::get('PLATBA360_SECRET'));
        return $launchUrl;
    }

    private function prepareQuery($orderId, $total) {
        $cart = $this->context->cart;

        $query = 'shopid=' . Configuration::get('PLATBA360_SHOP_ID');
        $query .= '&amount=' . $total;
        $query .= '&varsymbol=' . (int)$orderId;
        $query .= '&priority=NORM';
        $query .= '&url=' . Tools::getHttpHost(true).__PS_BASE_URI__ . 'module/ps_platba360/callback';
        return $query;
    }
      
    private function signQuery($query, $sign) {
        $hash = hash('sha256', $query . '&sign=' . $sign);
        return $query . '&sign=' . $hash;
    }
}
