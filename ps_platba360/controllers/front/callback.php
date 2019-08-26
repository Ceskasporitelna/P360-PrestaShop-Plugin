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

class Ps_Platba360CallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    
    public function initContent(){

        $orderId = Tools::getValue('varsymbol');

        $order = new Order ($orderId);
        $customer = new Customer ($order->id_customer);

        if($order->id != null){
            if($this->isValid($order)){
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;

                if(Tools::getValue('completed') == 'Y'){
                    $statusId = Configuration::get('PS_OS_PAYMENT');
                } else {
                    $statusId = Configuration::get('PS_OS_ERROR');
                }

                $history->changeIdOrderState($statusId, (int)($order->id));
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$order->id_cart.'&id_module='.(int)$this->module->id.'&id_order='.$order->id.'&key='.$order->secure_key);
            }
        } 
        Tools::redirect('index.php');
    }

    private function isValid($order){
        if(Tools::getValue('shopid') != Configuration::get('PLATBA360_SHOP_ID')){
            return false;
        }
        
        $hash = $this->getHash();

        if(Tools::getValue('sign') != $hash){
            return false;
        }
 
        return true;
    }

    private function getHash() {
        $pageUriWithoutParams = substr($this->context->shop->getBaseURL(), 0, -1).preg_replace('@(/?\?.*)|(/#.*)|(/$)@', '', $_SERVER['REQUEST_URI']);
        
        $query = 'shopid=' . Tools::getValue('shopid');
        $query .= '&amount=' . Tools::getValue('amount');
        $query .= '&varsymbol=' . Tools::getValue('varsymbol');
        $query .= '&priority=' . Tools::getValue('priority');
        $query .= '&trid=' . Tools::getValue('trid');
        $query .= '&completed=' . Tools::getValue('completed');
        $query .= '&sign=' . Configuration::get('PLATBA360_SECRET');
        
        $url = $pageUriWithoutParams . '?' . $query;

        return hash('sha256', $url);
    }
 }
