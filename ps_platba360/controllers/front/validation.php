<?php

/**
 * Module Platba360
 *
 * This source file is subject to the Open Software License v. 3.0 (OSL-3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to application@brainweb.cz so we can send you a copy..
 *
 * @author    Ceska sporitelna, a.s. <developers@csas.cz>
 * @copyright 2019 Ceska sporitelna, a.s.
 * @license   Licensed under the Open Software License version 3.0  https://opensource.org/licenses/OSL-3.0
 *
 * Payment gateway operator and support: www.csas.cz
 * Module development: www.csas.cz
 */

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

//        TODO: for production access use this url
//        $url = 'https://platba360.cz';

        $url = 'https://uat1-platba360.csast.csas.cz';

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
