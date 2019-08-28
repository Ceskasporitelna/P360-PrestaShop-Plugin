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
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Platba360 extends PaymentModule {
    private $_html = '';
    private $_postErrors = array();

    public $shopId;
    public $secret;
    public $extra_mail_vars;

    public function __construct() {
        $this->name = 'ps_platba360';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'ČSAS';
        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('PLATBA360_SHOP_ID', 'PLATBA360_SECRET'));
        if (isset($config['PLATBA360_SHOP_ID'])) {
            $this->shopId = $config['PLATBA360_SHOP_ID'];
        }
        if (isset($config['PLATBA360_SECRET'])) {
            $this->secret = $config['PLATBA360_SECRET'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Platba z účtu', array(), 'Modules.Platba360.Admin');
        $this->description = $this->trans('Usnadněte zákazníkům Vašeho e-shopu placení.', array(), 'Modules.Platba360.Admin');
        $this->confirmUninstall = $this->trans('Určitě chcete odstranit svoje nastavení tohoto modulu?', array(), 'Modules.Platba360.Admin');
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);

        if ((!isset($this->shopId) || !isset($this->secret) || empty($this->shopId) || empty($this->secret))) {
            $this->warning = $this->trans('"Shop ID" a "Secret" musí být vyplněno před použitím modulu.', array(), 'Modules.Platba360.Admin');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('Pro tento modul nebyla nastavena žádná měna.', array(), 'Modules.Platba360.Admin');
        }

        $this->extra_mail_vars = array();
    }

    public function install() {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn');
    }

    public function uninstall() {
        return Configuration::deleteByName('PLATBA360_SHOP_ID')
            && Configuration::deleteByName('PLATBA360_SECRET')
            && parent::uninstall();
    }

    public function getContent() {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayCheck();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params) {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
                ->setCallToActionText($this->trans('Zaplatit přes Platbu z účtu', array(), 'Modules.Platba360.Admin'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setAdditionalInformation($this->display(__FILE__, './views/templates/hook/ps_platba360_intro.tpl'));

        return [$newOption];
    }

    public function hookPaymentReturn($params) {
        if (!$this->active) {
            return;
        }
        $state = $params['order']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_BANKWIRE')))) {
            $this->smarty->assign(array(
                'status' => 'ok',
                'id_order' => $params['order']->id
            ));
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->fetch('module:ps_platba360/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart) {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm() {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Nastavení', array(), 'Modules.Platba360.Admin'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Shop ID', array(), 'Modules.Platba360.Admin'),
                        'name' => 'PLATBA360_SHOP_ID',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Secret', array(), 'Modules.Platba360.Admin'),
                        'name' => 'PLATBA360_SECRET',
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
        );

        $this->fields_form = array();

        $form = $helper->generateForm(array($fields_form));

        $this->context->smarty->assign('form_tpl', $form);
        return $this->fetch('module:ps_platba360/views/templates/hook/settings.tpl');
    }

    public function getConfigFieldsValues() {
        return array(
            'PLATBA360_SHOP_ID' => Tools::getValue('PLATBA360_SHOP_ID', Configuration::get('PLATBA360_SHOP_ID')),
            'PLATBA360_SECRET' => Tools::getValue('PLATBA360_SECRET', Configuration::get('PLATBA360_SECRET')),
        );
    }

    private function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('PLATBA360_SHOP_ID')) {
                $this->_postErrors[] = $this->trans('Pole "Shop ID" je povinné.', array(),'Modules.Platba360.Admin');
            } elseif (!Tools::getValue('PLATBA360_SECRET')) {
                $this->_postErrors[] = $this->trans('Pole "Secret" je povinné.', array(), 'Modules.Platba360.Admin');
            }
        }
    }

    private function _postProcess() {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PLATBA360_SHOP_ID', Tools::getValue('PLATBA360_SHOP_ID'));
            Configuration::updateValue('PLATBA360_SECRET', Tools::getValue('PLATBA360_SECRET'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Success'));
    }

    private function _displayCheck() {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }
}
