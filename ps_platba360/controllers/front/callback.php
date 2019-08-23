<?php

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
