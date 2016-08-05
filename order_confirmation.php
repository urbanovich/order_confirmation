<?php

/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*         DISCLAIMER   *
* *************************************** */

/* Do not edit or add to this file if you wish to upgrade Prestashop to newer
* versions in the future.
* *****************************************************
* @category   Belvg
* @package    order_confirmation.php
* @author     Dzmitry Urbanovich (urbanovich.mslo@gmail.com)
* @site       http://module-presta.com
* @copyright  Copyright (c) 2007 - 2016 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*/

if(!defined('_PS_VERSION_'))
    exit;

class order_confirmation extends Module
{

    public $_hooks = array(
        'displayPaymentReturn',
        'displayOrderConfirmation',
        'Header',
    );

    public function __construct()
    {
        $this->name = 'order_confirmation';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Belvg';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order confirmation');
        $this->description = $this->l('Create the new order confirmation pages.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('Order confirmation'))
            $this->warning = $this->l('No name provided');
    }

    public function install()
    {
        if (!parent::install()
            || !$result = $this->registerHook($this->_hooks))
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall())
            return false;

        //unregister hooks
        if (isset($this->_hooks) && !empty($this->_hooks))
        {
            foreach ($this->_hooks as $hook)
            {
                if (!empty($hook) && !$this->unregisterHook($hook))
                {
                    return false;
                }
            }
        }
        
        return true;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $oc_use_payment_return = intval(Tools::getValue('OC_USE_PAYMENT_RETURN'));
            if (!Validate::isBool($oc_use_payment_return))
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else
            {
                Configuration::updateValue('OC_USE_PAYMENT_RETURN', $oc_use_payment_return);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Display a payment data on the order confirmation page:'),
                    'name' => 'OC_USE_PAYMENT_RETURN',
                    'required' => true,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['OC_USE_PAYMENT_RETURN'] = Configuration::get('OC_USE_PAYMENT_RETURN');

        return $helper->generateForm($fields_form);
    }

    public function hookHeader()
    {
        if($this->context->controller->php_self != 'order-confirmation')
            return false;

        Media::addJsDef(array(
            'order_confirmation_id_order' => Tools::getValue('id_order', 0),
            'order_confirmation_file' => $this->context->link->getPageLink('order-detail', true, NULL, "id_order=" . Tools::getValue('id_order', 0)),
        ));
        
        $this->context->controller->addCSS(array(
            $this->getPathUri().'/views/css/history.css',
            $this->getPathUri().'/views/css/addresses.css'
        ));
        $this->context->controller->addJS(array(
            $this->getPathUri().'/views/js/history.js',
            $this->getPathUri().'/views/js/order_confirmation.js',
        ));
        $this->context->controller->addJqueryPlugin(array('scrollTo', 'footable', 'footable-sort', 'fancybox'));

    }
    
    public function hookDisplayOrderConfirmation($params)
    {
        return $this->display(__FILE__, 'order-confirmation.tpl');
    }
    
    /**
     * Add new page in back office
     *
     * @param type $class_name
     * @param type $tab_name
     * @param type $id_parent
     * @param type $position
     *
     * @return type
     */
    public function installModuleTab($class_name, $tab_name, $id_parent = 0, $position = 0)
    {

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $class_name;
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = $tab_name;

        $tab->id_parent = $id_parent;
        $tab->position = $position;
        $tab->module = $this->name;

        return $tab->save();
    }

    /**
     * Delete custom page of back office
     *
     * @param type $class_name
     *
     * @return type
     */
    public function uninstallModuleTab($class_name)
    {

        $id_tab = Tab::getIdFromClassName($class_name);

        if ($id_tab) {

            $tab = new Tab($id_tab);
            $tab->delete();
            return true;
        }

        return false;
    }
}