<?php


class VitatiendaShippingConditionController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->lang = false;
        $this->table = 'vitatiendashipping_condition';
        $this->className = 'VitatiendaShippingCondition';
        $this->identifier = 'id_condition';
        parent::__construct();
        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            Shop::addTableAssociation('vitatiendashipping_condition', array('type' => 'shop', 'primary' => 'id_condition'));
        }
        $this->_join .= VitatiendaShippingCondition::addSqlAssociationCustom('vitatiendashipping_condition', 'a', false);
        $this->_group = ' GROUP BY a.id_condition';

        $this->fields_list = array(
            'id_condition' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Condition name'),
                'align' => 'center',
            ),
            'shipping_price' => array(
                'title' => $this->l('Min. price'),
                'align' => 'center',
                'callback' => 'setPriceCurrency',
            ),
            // 'shipping_weight' => array(
            //     'title' => $this->l('Min. weight (kg)'),
            //     'align' => 'center',
            // ),
            'tax_inc' => array(
                'title' => $this->l('Tax includes'),
                'align' => 'center',
                'type' => 'bool',
                'callback' => 'changeTaxIncStatus',
            ),
            'handling_charge' => array(
                'title' => $this->l('Handling charge'),
                'align' => 'center',
                'type' => 'bool',
                'callback' => 'changeHandlingChargeStatus',
            ),
            'active' => array(
                'title' => $this->l('Status'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
            ),
        );
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        Media::addJsDef(
            array(
                'VITATIENDA_SHIPPING_ADMIN_APPROVE' => Configuration::get('VITATIENDA_SHIPPING_ADMIN_APPROVE'),
                'PS_SHIPPING_FREE_PRICE' => Configuration::get('PS_SHIPPING_FREE_PRICE'),
                'VITATIENDA_SHIPPING_VISIBILITY_PAGE' => Configuration::get('VITATIENDA_SHIPPING_VISIBILITY_PAGE'),
                'afsPlacholder' => $this->l('Click to choose'),
                'noRecord' => $this->l('No Record Found!'),
                'chooseValue' => $this->l('Choose zone !'),
            )
        );
    }

    public static function setPriceCurrency($price, $tableRow)
    {
        $currencyData = Currency::getCurrency($tableRow['id_currency']);
        return $currencyData['iso_code'].' '.$price;
    }

    public function changeTaxIncStatus($value, $tableRow)
    {
        $this->context->smarty->assign(
            array(
                'value' => $value,
                'tableRow' => $tableRow,
                'token' => Tools::getAdminTokenLite('VitatiendaShippingCondition'),
            )
        );

        return $this->context->smarty->fetch(_PS_MODULE_DIR_.
        'vitatiendashipping/views/templates/admin/changetaxincstatus.tpl');
    }

    public function changeHandlingChargeStatus($value, $tableRow)
    {
        $this->context->smarty->assign(
            array(
                'value' => $value,
                'tableRow' => $tableRow,
                'token' => Tools::getAdminTokenLite('VitatiendaShippingCondition'),
            )
        );

        return $this->context->smarty->fetch(_PS_MODULE_DIR_.
        'vitatiendashipping/views/templates/admin/changehandlingchargestatus.tpl');
    }

    public function initContent()
    {
        if (($this->display == 'edit') && (Shop::getContext() == Shop::CONTEXT_SHOP)) {
            if (!$this->loadObject(true)) {
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
            }
        }
        return parent::initContent();
    }

    public function initProcess()
    {
        parent::initProcess();
        if (Tools::isSubmit('changeTaxIncVal') && $this->id_object) {
            if ($this->access('edit')) {
                $this->action = 'change_tax_inc_val';
            } else {
                $this->errors[] = $this->l('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('changeHandlingChargeVal') && $this->id_object) {
            if ($this->access('edit')) {
                $this->action = 'change_handling_charge_val';
            } else {
                $this->errors[] = $this->l('You do not have permission to edit this.');
            }
        }
    }

    public function processChangeTaxIncVal()
    {
        $afsCondition = new VitatiendaShippingCondition($this->id_object);
        if (!Validate::isLoadedObject($afsCondition)) {
            $this->errors[] = $this->l('An error occurred while updating condition information.');
        }
        $afsCondition->tax_inc = $afsCondition->tax_inc ? 0 : 1;
        if (!$afsCondition->update()) {
            $this->errors[] = $this->l('An error occurred while updating condition information.');
        }
        Tools::redirectAdmin(self::$currentIndex . '&conf=5&token=' . $this->token);
    }

    public function processChangeHandlingChargeVal()
    {
        $afsCondition = new VitatiendaShippingCondition($this->id_object);
        if (!Validate::isLoadedObject($afsCondition)) {
            $this->errors[] = $this->l('An error occurred while updating condition information.');
        }
        $afsCondition->handling_charge = $afsCondition->handling_charge ? 0 : 1;
        if (!$afsCondition->update()) {
            $this->errors[] = $this->l('An error occurred while updating condition information.');
        }
        Tools::redirectAdmin(self::$currentIndex . '&conf=5&token=' . $this->token);
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
            'desc' => $this->l('New Condition'),
            'icon' => 'process-icon-new'
        );
        $this->page_header_toolbar_btn['view'] = array(
            'href' => $this->context->link->getAdminLink('AdminModules').'&configure=vitatiendashipping',
            'desc' => $this->l('Module Configuration'),
            'icon' => 'process-icon-cogs'
        );
    }

    public function renderForm()
    {
        if (($this->display == 'edit') && (Shop::getContext() != Shop::CONTEXT_SHOP)) {
            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/shop_warning.tpl'
            );
        } else {
            if (Tools::getValue('id_condition')) {
                $afsCondition = new VitatiendaShippingCondition();
                $conditionData = $afsCondition->getConditionById(Tools::getValue('id_condition'));
                $conditionZones = $afsCondition->getZoneByConditionId(Tools::getValue('id_condition'));
                $conditionCountries = $afsCondition->getCountryByConditionId(Tools::getValue('id_condition'));
                $conditionCarriers = $afsCondition->getCarrierByConditionId(Tools::getValue('id_condition'));
                $addedZone = array();
                if (is_array($conditionZones) && !empty($conditionZones)) {
                    foreach ($conditionZones as $conditionZone) {
                        $addedZone[] = $conditionZone['id_zone'];
                    }
                }
                if (isset($addedZone)) {
                    $this->context->smarty->assign(
                        array(
                            'addedZone' => $addedZone,
                            'addedZoneStr' => implode(',', $addedZone),
                        )
                    );
                }
                $addedCountry = array();
                if (is_array($conditionCountries) && !empty($conditionCountries)) {
                    foreach ($conditionCountries as $conditionCountry) {
                        if ($conditionCountry['id_country']) {
                            $addedCountry[] = $conditionCountry['id_country'];
                        }
                    }
                }
                if (isset($addedCountry)) {
                    $this->context->smarty->assign(
                        array(
                            'addedCountry' => $addedCountry,
                            'addedCountryIds' => implode(',', $addedCountry),
                        )
                    );
                }
                $addedCarriers = array();
                if (is_array($conditionCarriers) && !empty($conditionCarriers)) {
                    foreach ($conditionCarriers as $conditionCarrier) {
                        $addedCarriers[] = $conditionCarrier['id_carrier'];
                    }
                }
                if (isset($addedCarriers)) {
                    $this->context->smarty->assign('addedCarriers', $addedCarriers);
                }
                $countryLists = array();
                $country = new Country();
                if (is_array($addedZone) && !empty($addedZone)) {
                    foreach ($addedZone as $idZone) {
                        $countryLists[] = $country->getCountriesByZoneId(
                            $idZone,
                            $this->context->language->id
                        );
                    }
                }
                $countryUnsorted = array();
                if (is_array($countryLists) && !empty($countryLists)) {
                    foreach ($countryLists as $countryList) {
                        foreach ($countryList as $countrys) {
                            $countryUnsorted[] = $countrys;
                        }
                    }
                }
                uasort($countryUnsorted, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                $countrySorted = array();
                $sortedCountryList = array();
                if (is_array($countryUnsorted) && !empty($countryUnsorted)) {
                    foreach ($countryUnsorted as $sortedList) {
                        $countrySorted['id_country'] = $sortedList['id_country'];
                        $countrySorted['name'] = $sortedList['name'];
                        $sortedCountryList[] = $countrySorted;
                    }
                }
                if (isset($sortedCountryList)) {
                    $this->context->smarty->assign('sortedCountryList', $sortedCountryList);
                }
                //dump($conditionData['id_user_group']); die();
                $this->context->smarty->assign(
                    array(
                        'addedGroups' => explode(",",$conditionData['id_user_group']),
                        'conditionData' => $conditionData,
                        'editMsg' => 1,
                    )
                );
            }           
            $zoneData = Zone::getZones();
            $carrierData = Carrier::getCarriers($this->context->language->id);
            $groupData = Group::getGroups($this->context->language->id);
            $currencyData = Currency::getCurrencies();
            $this->context->smarty->assign(
                array(
                    'defaultCurrency' => $this->context->currency->sign,
                    'currencyData' => $currencyData,
                    'zoneData' => $zoneData,
                    'carrierData' => $carrierData,
                    'groupData' => $groupData,
                )
            );
            $this->fields_form = array(
                    'submit' => array(
                    'title' => $this->l('Save'),
                ),
            );
            return parent::renderForm();
        }
    }

    public function processSave()
    {
        if (Tools::isSubmit('submitCondition')) {
            $idCondition = Tools::getValue('id_condition');
            $name = Tools::getValue('name');
            $shippingPrice = Tools::getValue('shipping_price');
            $idCurrency = Tools::getValue('currency');
            // $shippingWeight = Tools::getValue('shipping_weight');
            $taxInc = Tools::getValue('tax_inc');
            $handlingCharge = Tools::getValue('handling_charge');
            $zoneIds = explode(',', Tools::getValue('zoneIds'));
            $countrys = Tools::getValue('country');
            $idCarriers = Tools::getValue('id_carriers');
            $idGroups = Tools::getValue('id_groups');
            $active = Tools::getValue('active');

            $this->validatePostFields();
            if (empty($this->errors)) {
                if ($idCondition) {
                    $afsCondition = new VitatiendaShippingCondition($idCondition);
                } else {
                    $afsCondition = new VitatiendaShippingCondition();
                }
                $afsCondition->name = $name;
                $afsCondition->shipping_price = $shippingPrice;
                $afsCondition->id_currency = $idCurrency;
                //$afsCondition->shipping_weight = $shippingWeight;
                $afsCondition->tax_inc = $taxInc;
                $afsCondition->handling_charge = $handlingCharge;
                $afsCondition->active = $active;
                $afsCondition->save();
                $insertedId = $afsCondition->id;
                $afsCondition->deleteLocation($idCondition);
                $afsCondition->deleteUserGroups($idCondition);

                if (is_array($idGroups) && !empty($idGroups)) {
                    foreach ($idGroups as $idGroup) {
                        $afsCondition->createAfsUserGroup(
                            $idGroup,
                            $insertedId
                        );
                    }
                }

                if (is_array($idCarriers) && !empty($idCarriers)) {
                    foreach ($idCarriers as $idCarrier) {
                        $carrier = new Carrier($idCarrier);
                        $carrier->setGroups($idGroups);
                        if (is_array($zoneIds) && !empty($zoneIds)) {
                            foreach ($zoneIds as $zoneId) {
                                $country = new Country;
                                $countryLists = $country->getCountriesByZoneId(
                                    $zoneId,
                                    $this->context->language->id
                                );
                                $allCountryIdByZone = array();
                                if (is_array($countryLists) && !empty($countryLists)) {
                                    foreach ($countryLists as $countryList) {
                                        $allCountryIdByZone[] = $countryList['id_country'];
                                    }
                                }
                                $carrierReferenceId = $carrier->id_reference;
                                if (is_array($countrys) && !empty($countrys)) {
                                    foreach ($countrys as $countries) {
                                        if (in_array($countries, $allCountryIdByZone)) {
                                            $afsCondition->createAfsLocation(
                                                $idCarrier,
                                                $carrierReferenceId,
                                                $zoneId,
                                                $countries,
                                                $insertedId
                                            );
                                        }
                                    }
                                } else {
                                    $countries = 0;
                                    $afsCondition->createAfsLocation(
                                        $idCarrier,
                                        $carrierReferenceId,
                                        $zoneId,
                                        $countries,
                                        $insertedId
                                    );
                                }
                            }
                        }
                    }
                }
                if ($idCondition) {
                    Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
                } else {
                    Tools::redirectAdmin(self::$currentIndex.'&conf=3&token='.$this->token);
                }
            } else {
                $this->display = "edit";
            }
        }
    }

    public function validatePostFields()
    {
        $name = Tools::getValue('name');
        $shippingPrice = Tools::getValue('shipping_price');
        $shippingWeight = Tools::getValue('shipping_weight');
        $zoneIds = Tools::getValue('zoneIds');
        $idCarriers = Tools::getValue('id_carriers');
        if (!$name) {
            $this->errors[] = $this->l('Condition name is required.');
        } elseif (!Validate::isString($name)) {
            $this->errors[] = $this->l('Condition name must be valid.');
        }
        if (!$shippingPrice) {
            $this->errors[] = $this->l('Shipping price is required.');
        } elseif (Tools::strlen($shippingPrice) > 10) {
            $this->errors[] = $this->l('Shipping price only allow 10 digits.');
        } elseif (!Validate::isFloat($shippingPrice)) {
            $this->errors[] = $this->l('Shipping price should be valid or greater than zero (0).');
        }
        // if (!$shippingWeight) {
        //     $this->errors[] = $this->l('Shipping weight is required.');
        // } elseif (!Validate::isWeightUnit($shippingWeight)) {
        //     $this->errors[] = $this->l('Shipping weight should be valid or greater than zero (0).');
        // }
        if (!$zoneIds) {
            $this->errors[] = $this->l('Select at least one zone.');
        }
        if (!$idCarriers) {
            $this->errors[] = $this->l('Select at least one carrier.');
        }

        return $this->errors;
    }

    public function ajaxProcessGetCountryByZoneId()
    {
        if (Tools::getValue('id_zone')) {
            $idZones = Tools::getValue('id_zone');
            $countryLists = array();
            $country = new Country();
            if (is_array($idZones) && !empty($idZones)) {
                foreach ($idZones as $idZone) {
                    $countryLists[] = $country->getCountriesByZoneId(
                        $idZone,
                        $this->context->language->id
                    );
                }
            }
            $countryUnsorted = array();
            if (is_array($countryLists) && !empty($countryLists)) {
                foreach ($countryLists as $countryList) {
                    foreach ($countryList as $countrys) {
                        $countryUnsorted[] = $countrys;
                    }
                }
            }
            uasort($countryUnsorted, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            $sortedCountryList = array();
            $countrySorted = array();
            if (is_array($countryUnsorted) && !empty($countryUnsorted)) {
                foreach ($countryUnsorted as $sortedList) {
                    $countrySorted['id_country'] = $sortedList['id_country'];
                    $countrySorted['name'] = $sortedList['name'];
                    $sortedCountryList[] = $countrySorted;
                }
            }
            die(json_encode($sortedCountryList));
        }
    }
}
