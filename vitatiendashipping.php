<?php


require_once 'classes/VitatiendaShippingCondition.php';

class VitaTiendaShipping extends Module
{
    public function __construct()
    {
        $this->name = 'vitatiendashipping';
        $this->tab = 'front_office_features';
        $this->displayName = $this->l('Vitatienda Shipping');
        $this->description = $this->l('Vitatienda free shipping.');
        $this->version = '0.0.1';
        $this->author = 'Cesar Quintini';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()
            || !$this->createTable()
            || !$this->registerHook('actionAdminControllerSetMedia')
            || !$this->registerHook('displayCheckoutSubtotalDetails')
            || !$this->registerHook('displayProductAdditionalInfo')
            || !$this->registerHook('displayCheckoutSummaryTop')
            || !$this->callInstallTab()) {
            return false;
        }
        Configuration::updateValue('VITATIENDA_SHIPPING_SHIPPING_FREE_PRICE', Configuration::get('PS_SHIPPING_FREE_PRICE'));
        Configuration::updateValue('VITATIENDA_SHIPPING_ADMIN_APPROVE', null);
        Configuration::updateValue('PS_SHIPPING_FREE_PRICE', 0);
        return true;
    }

    public function callInstallTab()
    {
        $this->installTab('AfsModule', 'Avail Free Shipping');
        $this->installTab('VitatiendaShippingCondition', 'Manage Conditions', 'AfsModule');
        return true;
    }

    public function installTab($className, $tabName, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        // Add icon in the left menu for a parent Controller
        if ($className =='VitatiendaShippingCondition') {
            $tab->icon = 'local_shipping';
        }
        if ($tabParentName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = 0;
        }
        $tab->module = $this->name;
        return $tab->add();
    }

    public function createTable()
    {
        $success = true;
        $db = Db::getInstance();
        $queries = $this->getDbTableQueries();
        foreach ($queries as $query) {
            $success &= $db->execute($query);
        }

        return $success;
    }

    public function hookActionAdminControllerSetMedia()
    {
        // To add JS & CSS file in back office on module config page
        if (('AdminModules' == Tools::getValue('controller')
            && 'vitatiendashipping' == Tools::getValue('configure'))
            || ('VitatiendaShippingCondition' == Tools::getValue('controller'))) {
            $this->context->controller->addJS($this->_path.'views/js/wkafsconfiguration.js');
            $this->context->controller->addCSS($this->_path.'views/css/wkafsstyle.css');
        }
    }

    /**
     * Function to return the price & weight for free shipping by satisfied condition
     *
     * @return array
     */
    public function getWeightAndPriceForFreeShipping()
    {
        if ($this->context->cart->id_address_delivery) {
            $userAddressCountry = $this->context->cart->id_address_delivery;
        } else {
            $userAddressCountry = $this->context->country->id;
        }
        $addressObj = new Address($userAddressCountry);
        if ($this->context->cart->id_address_delivery) {
            $wkIdCountry = $addressObj->id_country;
        } else {
            $wkIdCountry = $this->context->country->id;
        }
        $wkIdZone = Country::getIdZone($wkIdCountry);
        $idCarrier = $this->context->cart->id_carrier;
        $afsCondition = new VitatiendaShippingCondition();

        $resultByZone = $afsCondition->checkLocationForShippingByZone(
            (new Carrier($idCarrier))->id_reference,
            $wkIdZone
        );

        $conditionCountry = array();
        foreach ($resultByZone as $rs) {
            $conditionCountry[] = $rs['id_country'];
        }
        if (in_array($wkIdCountry, $conditionCountry)) {
            $result = $afsCondition->checkLocationForShipping(
                (new Carrier($idCarrier))->id_reference,
                $wkIdZone,
                $wkIdCountry
            );
        } else {
            $result = $afsCondition->checkLocationForShipping(
                (new Carrier($idCarrier))->id_reference,
                $wkIdZone,
                0
            );
        }

        if (Configuration::get('VITATIENDA_SHIPPING_ADMIN_APPROVE')) {
            if (!empty($result['id_condition'])) {
                $priceForAfs = Tools::convertPriceFull(
                    $result['shipping_price'],
                    Currency::getCurrencyInstance((int) $result['id_currency']),
                    Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT'))
                );
                $priceForAfsRound = (float)Tools::ps_round((float)$priceForAfs, 2);
                if ($priceForAfsRound <= Configuration::get('PS_SHIPPING_FREE_PRICE')) {
                    if ($result['id_currency'] == $this->context->cart->id_currency) {
                        $priceToAfs = $result['shipping_price'];
                    } else {
                        $priceToAfsConverted = Tools::convertPriceFull(
                            $result['shipping_price'],
                            Currency::getCurrencyInstance((int) $result['id_currency']),
                            Currency::getCurrencyInstance((int) $this->context->cart->id_currency)
                        );
                        $priceToAfs = (float)Tools::ps_round((float)$priceToAfsConverted, 2);
                    }
                    $weightToAfs = $result['shipping_weight'];
                } else {
                    if ($this->context->cart->id_currency == Configuration::get('PS_CURRENCY_DEFAULT')) {
                        $priceToAfs = Configuration::get('PS_SHIPPING_FREE_PRICE');
                    } else {
                        $priceToAfsConverted = Tools::convertPriceFull(
                            Configuration::get('PS_SHIPPING_FREE_PRICE'),
                            Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT')),
                            Currency::getCurrencyInstance((int) $this->context->cart->id_currency)
                        );
                        $priceToAfs = (float)Tools::ps_round((float)$priceToAfsConverted, 2);
                    }
                    if (Configuration::get('PS_SHIPPING_FREE_WEIGHT') > 0) {
                        $weightToAfs = Configuration::get('PS_SHIPPING_FREE_WEIGHT');
                    } else {
                        $weightToAfs = 1000; //default value 1000 means condition disabled
                    }
                }
            } else {
                if ($this->context->cart->id_currency == Configuration::get('PS_CURRENCY_DEFAULT')) {
                    $priceToAfs = Configuration::get('PS_SHIPPING_FREE_PRICE');
                } else {
                    $priceToAfsConverted = Tools::convertPriceFull(
                        Configuration::get('PS_SHIPPING_FREE_PRICE'),
                        Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT')),
                        Currency::getCurrencyInstance((int) $this->context->cart->id_currency)
                    );
                    $priceToAfs = (float)Tools::ps_round((float)$priceToAfsConverted, 2);
                }
                if (Configuration::get('PS_SHIPPING_FREE_WEIGHT') > 0) {
                    $weightToAfs = Configuration::get('PS_SHIPPING_FREE_WEIGHT');
                } else {
                    $weightToAfs = 1000; //default value 1000 means condition disabled
                }
            }
        } else {
            if ($this->context->cart->id_currency == $result['id_currency']) {
                $priceToAfs = $result['shipping_price'];
            } else {
                $priceToAfsConverted = Tools::convertPriceFull(
                    $result['shipping_price'],
                    Currency::getCurrencyInstance((int) $result['id_currency']),
                    Currency::getCurrencyInstance((int) $this->context->cart->id_currency)
                );
                $priceToAfs = (float)Tools::ps_round((float)$priceToAfsConverted, 2);
            }
            $weightToAfs = $result['shipping_weight'];
        }
        $contextData = Context::getContext();
        $allTplVariables = $contextData->smarty->tpl_vars;
        $currentCartData = $allTplVariables['cart']->value;
        $shippingAmountOnOrder = $currentCartData['subtotals']['shipping']['amount'];
        $productTotal = 0;
        $productWeight = 0;
        foreach ($this->context->cart->getProducts() as $presentCart) {
            if (isset($presentCart['total_wt'])) {
                $productTotal += $presentCart['total_wt'];
            }
            $productWeight += $presentCart['cart_quantity'] * $presentCart['weight'];
        }

        return array(
            'priceToAfs' => $priceToAfs,
            'weightToAfs' => $weightToAfs,
            'productTotal' => $productTotal,
            'productWeight' => $productWeight,
            'additionalShipping' => $shippingAmountOnOrder,
        );
    }

    /**
     * Function to get all carrier according the zone id
     *
     * @param int $wkIdZone
     * @return array
     */
    public function getFreeShippingCarriers($wkIdZone)
    {
        $idLang = $this->context->language->id;
        $carriersByZone = Carrier::getCarriers($idLang, 0, 0, $wkIdZone);
        $isCarrierFree = array();
        foreach ($carriersByZone as $carrierByZones) {
            $isCarrierFree[] = $carrierByZones['is_free'];
        }

        return $isCarrierFree;
    }

    /**
     * Function to show free shipping alert on cart page
     */
    public function hookDisplayCheckoutSubtotalDetails()
    {
        if ('cart' == $this->context->controller->php_self) {
            $afsResult = $this->getWeightAndPriceForFreeShipping();
            $priceToAfs = $afsResult['priceToAfs'];
            $weightToAfs = $afsResult['weightToAfs'];
            $productTotal = $afsResult['productTotal'];
            $productWeight = $afsResult['productWeight'];
            $additionalShipping = $afsResult['additionalShipping'];

            if ('cart' == $this->context->controller->php_self) {
                if (!empty(json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')))) {
                    if (in_array(1, json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')))) {
                        if ($productTotal < $priceToAfs  && $productWeight < $weightToAfs) {
                            if ($additionalShipping > 0) {
                                $moreAmt = $priceToAfs - $productTotal;
                                if ($moreAmt > 0) {
                                    $this->context->smarty->assign(
                                        array(
                                            'required_amt_free_shipping' => $moreAmt,
                                            'currency_symbol' => $this->context->currency->sign,
                                            'shipping_msg' => 1
                                        )
                                    );
                                }

                                return $this->fetch(
                                    'module:vitatiendashipping/views/templates/hook/avail-free-shipping-alert.tpl'
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Function to show free shipping alert on product detail page
     */
    public function hookDisplayProductAdditionalInfo()
    {
        if ('product' == $this->context->controller->php_self) {
            $afsResult = $this->getWeightAndPriceForFreeShipping();
            $priceToAfs = $afsResult['priceToAfs'];
            $weightToAfs = $afsResult['weightToAfs'];
            $productTotal = $afsResult['productTotal'];
            $productWeight = $afsResult['productWeight'];
            $additionalShipping = $afsResult['additionalShipping'];
            if ('product' == $this->context->controller->php_self) {
                if (!empty(json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')))) {
                    if (in_array(3, json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')))) {
                        if ($productTotal < $priceToAfs  && $productWeight < $weightToAfs) {
                            if ($additionalShipping > 0) {
                                $moreAmt = $priceToAfs - $productTotal;
                                if ($moreAmt > 0) {
                                    $this->context->smarty->assign(
                                        array(
                                            'required_amt_free_shipping' => $moreAmt,
                                            'currency_symbol' => $this->context->currency->sign,
                                            'shipping_msg' => 1
                                        )
                                    );
                                }

                                return $this->fetch(
                                    'module:vitatiendashipping/views/templates/hook/avail-free-shipping-alert.tpl'
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Function to show free shipping alert on checkout page
     */
    public function hookDisplayCheckoutSummaryTop()
    {
        if ('order' == $this->context->controller->php_self) {
            $afsResult = $this->getWeightAndPriceForFreeShipping();
            $priceToAfs = $afsResult['priceToAfs'];
            $weightToAfs = $afsResult['weightToAfs'];
            $productTotal = $afsResult['productTotal'];
            $productWeight = $afsResult['productWeight'];
            $additionalShipping = $afsResult['additionalShipping'];
            if ('order' == $this->context->controller->php_self) {
                if (!empty(json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')))) {
                    if (in_array(2, json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')))) {
                        if ($productTotal < $priceToAfs  && $productWeight < $weightToAfs) {
                            if ($additionalShipping > 0) {
                                $moreAmt = $priceToAfs - $productTotal;
                                if ($moreAmt > 0) {
                                    $this->context->smarty->assign(
                                        array(
                                            'required_amt_free_shipping' => $moreAmt,
                                            'currency_symbol' => $this->context->currency->sign,
                                            'shipping_msg' => 1
                                        )
                                    );
                                }

                                return $this->fetch(
                                    'module:vitatiendashipping/views/templates/hook/avail-free-shipping-alert.tpl'
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitModuleConfig')) {
            if (Tools::getValue('VITATIENDA_SHIPPING_ADMIN_APPROVE')) {
                $afsAmount = Tools::getValue('PS_SHIPPING_FREE_PRICE');
                if (!$afsAmount) {
                    $this->errors[] = $this->l('Shipping price required or should be greater than zero (0).');
                } elseif (!Validate::isPrice($afsAmount)) {
                    $this->errors[] = $this->l('Shipping price should be numeric or less than 10 digits.');
                }
            } else {
                $visibilityPage = json_encode(Tools::getValue('VITATIENDA_SHIPPING_VISIBILITY_PAGE'));
                Configuration::updateValue('VITATIENDA_SHIPPING_ADMIN_APPROVE', null);
                Configuration::updateValue('PS_SHIPPING_FREE_PRICE', 0);
                Configuration::updateValue('VITATIENDA_SHIPPING_VISIBILITY_PAGE', $visibilityPage);
                Tools::redirectAdmin(
                    Context::getContext()->link->getAdminLink('AdminModules').'&configure=vitatiendashipping&conf=4'
                );
            }

            if (empty($this->errors)) {
                $this->postProcess();
                Tools::redirectAdmin(
                    Context::getContext()->link->getAdminLink('AdminModules').'&configure=vitatiendashipping&conf=4'
                );
            } else {
                foreach ($this->errors as $err) {
                    $this->context->controller->errors[] = $this->l($err);
                }
            }
        }
        //if (Shipping > Preferences) free shipping amount zero than it will be no as default in module congiguration
        if (!Configuration::get('PS_SHIPPING_FREE_PRICE')) {
            Configuration::updateValue('VITATIENDA_SHIPPING_ADMIN_APPROVE', null);
        } else {
            Configuration::updateValue('VITATIENDA_SHIPPING_ADMIN_APPROVE', 1);
        }
        Media::addJsDef(
            array(
                'PS_SHIPPING_FREE_PRICE' => Configuration::get('PS_SHIPPING_FREE_PRICE')
            )
        );

        return $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModuleConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        $pages = array(
            array('id_wk_page' => '1', 'name_wk_page' => $this->l('Cart')),
            array('id_wk_page' => '2', 'name_wk_page' => $this->l('Checkout')),
            array('id_wk_page' => '3', 'name_wk_page' => $this->l('Product'))
        );
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Configuration settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Global free shipping'),
                        'hint' => $this->l('If No then free shipping gets applied on the basis of their specific
                        condition only. '),
                        'name' => 'VITATIENDA_SHIPPING_ADMIN_APPROVE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Minimum cart value for free shipping'),
                        'desc' => $this->l('Amount will be tax & shipping excl.'),
                        'class' => 'fixed-width-xxl wk-show-hide',
                        'name' => 'PS_SHIPPING_FREE_PRICE',
                        'suffix' => $this->context->currency->sign,
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'VITATIENDA_SHIPPING_VISIBILITY_PAGE[]',
                        'label' => $this->l('Choose page to show message'),
                        'desc' => $this->l('Select page where you want to show the shipping message.'),
                        'class' => 'chosen',
                        'multiple' => true,
                        'options' => array(
                            'query' => $pages,
                            'id' => 'id_wk_page',
                            'name' => 'name_wk_page',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        if (Tools::getValue('VITATIENDA_SHIPPING_ADMIN_APPROVE')) {
            Media::addJsDef(
                array(
                    'VITATIENDA_SHIPPING_ADMIN_APPROVE' => Tools::getValue('VITATIENDA_SHIPPING_ADMIN_APPROVE'),
                )
            );
            return array(
                'VITATIENDA_SHIPPING_ADMIN_APPROVE' => Tools::getValue('VITATIENDA_SHIPPING_ADMIN_APPROVE'),
                'PS_SHIPPING_FREE_PRICE' => Tools::getValue('PS_SHIPPING_FREE_PRICE'),
                'VITATIENDA_SHIPPING_VISIBILITY_PAGE[]' => json_encode(Tools::getValue('VITATIENDA_SHIPPING_VISIBILITY_PAGE')),
            );
        } else {
            Media::addJsDef(
                array(
                    'VITATIENDA_SHIPPING_ADMIN_APPROVE' => Configuration::get('VITATIENDA_SHIPPING_ADMIN_APPROVE'),
                )
            );
            return array(
                'VITATIENDA_SHIPPING_ADMIN_APPROVE' => Configuration::get('VITATIENDA_SHIPPING_ADMIN_APPROVE'),
                'PS_SHIPPING_FREE_PRICE' => Configuration::get('PS_SHIPPING_FREE_PRICE'),
                'VITATIENDA_SHIPPING_VISIBILITY_PAGE[]' => json_decode(Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE')),
            );
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $formValues = $this->getConfigFormValues();
        foreach ($formValues as $key => $value) {
            if ('VITATIENDA_SHIPPING_VISIBILITY_PAGE[]' == $key) {
                $key = 'VITATIENDA_SHIPPING_VISIBILITY_PAGE';
            }
            Configuration::updateValue($key, $value);
        }
    }

    private function getDbTableQueries()
    {
        return array(
            "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."vitatiendashipping_condition` (
                `id_condition` int(10) unsigned NOT NULL auto_increment,
                `name` varchar(255) NOT NULL,
                `shipping_price` decimal(20,2) NOT NULL DEFAULT '0.00',
                `id_currency` int(10) NOT NULL,
                `shipping_weight` decimal(20,2) NOT NULL DEFAULT '0.00',
                `tax_inc` tinyint(1) unsigned NOT NULL,
                `handling_charge` tinyint(1) unsigned NOT NULL,
                `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY  (`id_condition`)
            ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."vitatiendashipping_condition_location` (
                `id_location` int(10) unsigned NOT NULL auto_increment,
                `id_condition` int(10) NOT NULL,
                `id_carrier` int(10) DEFAULT NULL,
                `id_carrier_reference` int(10) DEFAULT NULL,
                `id_zone` int(10) DEFAULT NULL,
                `id_country` int(10) DEFAULT NULL,
                PRIMARY KEY  (`id_location`)
              ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8",
              "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "vitatiendashipping_condition_shop` (
                `id_condition` int(10) NOT NULL ,
                `id_shop` int(10) NOT NULL,
                PRIMARY KEY  (`id_condition`, `id_shop`)
              ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8",
              "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "vitatiendashipping_condition_user_group` (
                `id_condition` int(10) NOT NULL ,
                `id_user_group` int(10) NOT NULL,
                PRIMARY KEY  (`id_condition`, `id_user_group`)
              ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8"
        );
    }

    public function uninstall()
    {
        Configuration::updateValue('PS_SHIPPING_FREE_PRICE', Configuration::get('VITATIENDA_SHIPPING_SHIPPING_FREE_PRICE'));
        if (!parent::uninstall()
            || !$this->uninstallTab()
            || !$this->deleteTables()
            || !$this->deleteConfigKeys()) {
            return false;
        }

        return true;
    }

    protected function deleteTables()
    {
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS
            `'._DB_PREFIX_.'vitatiendashipping_condition`,
            `'._DB_PREFIX_.'vitatiendashipping_condition_user_group`,
            `'._DB_PREFIX_.'vitatiendashipping_condition_location`,
            `'._DB_PREFIX_.'vitatiendashipping_condition_shop`;
        ');
    }

    public function uninstallTab()
    {
        $moduleTabs = Tab::getCollectionFromModule($this->name);
        if (!empty($moduleTabs)) {
            foreach ($moduleTabs as $moduleTab) {
                $moduleTab->delete();
            }
        }

        return true;
    }

    public function deleteConfigKeys()
    {
        $keys = array(
            'VITATIENDA_SHIPPING_ADMIN_APPROVE',
            'VITATIENDA_SHIPPING_VISIBILITY_PAGE',
            'VITATIENDA_SHIPPING_SHIPPING_FREE_PRICE',
        );
        foreach ($keys as $key) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        return true;
    }
}
