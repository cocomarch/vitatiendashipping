<?php


class Cart extends CartCore
{
    /**
     * Function to check free shipping according
     *
     * @param int          $id_carrier      Carrier ID
     * @param int|null     $id_zone         Zone ID
     * @param int|null     $id_country      Country ID
     *
     */

    public function getPackageShippingCost(
        $id_carrier = null,
        $use_tax = true,
        Country $default_country = null,
        $product_list = null,
        $id_zone = null
    ) {
        if ($this->isVirtualCart()) {
            return 0;
        }

        if (!$default_country) {
            $default_country = Context::getContext()->country;
        }

        if (!is_null($product_list)) {
            foreach ($product_list as $key => $value) {
                if ($value['is_virtual'] == 1) {
                    unset($product_list[$key]);
                }
            }
        }

        if (is_null($product_list)) {
            $products = $this->getProducts(false, false, null, false);
        } else {
            $products = $product_list;
        }

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address_id = (int)$this->id_address_invoice;
        } elseif (count($product_list)) {
            $prod = current($product_list);
            $address_id = (int)$prod['id_address_delivery'];
        } else {
            $address_id = null;
        }
        if (!Address::addressExists($address_id)) {
            $address_id = null;
        }

        if (is_null($id_carrier) && !empty($this->id_carrier)) {
            $id_carrier = (int)$this->id_carrier;
        }

        $cache_id = 'getPackageShippingCost_'
        .(int)$this->id.'_'
        .(int)$address_id.'_'
        .(int)$id_carrier.'_'
        .(int)$use_tax.'_'
        .(int)$default_country->id.'_'
        .(int)$id_zone;
        if ($products) {
            foreach ($products as $product) {
                $cache_id .= '_'.(int)$product['id_product'].'_'.(int)$product['id_product_attribute'];
            }
        }

        if (Cache::isStored($cache_id)) {
            return Cache::retrieve($cache_id);
        }

        // Order total in default currency without fees
        $order_total = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, $product_list);

        // Start with shipping cost at 0
        $shipping_cost = 0;
        // If no product added, return 0
        if (!count($products)) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        if (!isset($id_zone)) {
            // Get id zone
            if (!$this->isMultiAddressDelivery()
                && isset($this->id_address_delivery) // Be carefull, id_address_delivery is not usefull one 1.5
                && $this->id_address_delivery
                && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)
            ) {
                $id_zone = Address::getZoneById((int)$this->id_address_delivery);
            } else {
                if (!Validate::isLoadedObject($default_country)) {
                    $default_country = new Country(
                        Configuration::get('PS_COUNTRY_DEFAULT'),
                        Configuration::get('PS_LANG_DEFAULT')
                    );
                }

                $id_zone = (int)$default_country->id_zone;
            }
        }

        if ($id_carrier && !$this->isCarrierInRange((int)$id_carrier, (int)$id_zone)) {
            $id_carrier = 0;
        }

        if (empty($id_carrier) && $this->isCarrierInRange(
            (int)Configuration::get('PS_CARRIER_DEFAULT'),
            (int)$id_zone
        )) {
            $id_carrier = (int)Configuration::get('PS_CARRIER_DEFAULT');
        }

        $total_package_without_shipping_tax_inc = $this->getOrderTotal(
            true,
            Cart::BOTH_WITHOUT_SHIPPING,
            $product_list
        );
        if (empty($id_carrier)) {
            if ((int)$this->id_customer) {
                $customer = new Customer((int)$this->id_customer);
                $result = Carrier::getCarriers(
                    (int)Configuration::get('PS_LANG_DEFAULT'),
                    true,
                    false,
                    (int)$id_zone,
                    $customer->getGroups()
                );
                unset($customer);
            } else {
                $result = Carrier::getCarriers((int)Configuration::get('PS_LANG_DEFAULT'), true, false, (int)$id_zone);
            }

            foreach ($result as $k => $row) {
                if ($row['id_carrier'] == Configuration::get('PS_CARRIER_DEFAULT')) {
                    continue;
                }

                if (!isset(self::$_carriers[$row['id_carrier']])) {
                    self::$_carriers[$row['id_carrier']] = new Carrier((int)$row['id_carrier']);
                }

                /** @var Carrier $carrier */
                $carrier = self::$_carriers[$row['id_carrier']];

                $shipping_method = $carrier->getShippingMethod();
                // Get only carriers that are compliant with shipping method
                if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT
                    && $carrier->getMaxDeliveryPriceByWeight((int)$id_zone) === false)
                    || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE
                    && $carrier->getMaxDeliveryPriceByPrice((int)$id_zone) === false)) {
                    unset($result[$k]);
                    continue;
                }

                // If out-of-range behavior carrier is set on "Desactivate carrier"
                if ($row['range_behavior']) {
                    $check_delivery_price_by_weight = Carrier::checkDeliveryPriceByWeight(
                        $row['id_carrier'],
                        $this->getTotalWeight(),
                        (int)$id_zone
                    );

                    $total_order = $total_package_without_shipping_tax_inc;
                    $check_delivery_price_by_price = Carrier::checkDeliveryPriceByPrice(
                        $row['id_carrier'],
                        $total_order,
                        (int)$id_zone,
                        (int)$this->id_currency
                    );

                    // Get only carriers that have a range compatible with cart
                    if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !$check_delivery_price_by_weight)
                        || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && !$check_delivery_price_by_price)) {
                        unset($result[$k]);
                        continue;
                    }
                }

                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping = $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), (int)$id_zone);
                } else {
                    $shipping = $carrier->getDeliveryPriceByPrice($order_total, (int)$id_zone, (int)$this->id_currency);
                }
                $min_shipping_price = 0;
                if (!isset($min_shipping_price)) {
                    $min_shipping_price = $shipping;
                }

                if ($shipping <= $min_shipping_price) {
                    $id_carrier = (int)$row['id_carrier'];
                    $min_shipping_price = $shipping;
                }
            }
        }

        if (empty($id_carrier)) {
            $id_carrier = Configuration::get('PS_CARRIER_DEFAULT');
        }

        if (!isset(self::$_carriers[$id_carrier])) {
            self::$_carriers[$id_carrier] = new Carrier((int)$id_carrier, Configuration::get('PS_LANG_DEFAULT'));
        }

        $carrier = self::$_carriers[$id_carrier];

        // No valid Carrier or $id_carrier <= 0 ?
        if (!Validate::isLoadedObject($carrier)) {
            Cache::store($cache_id, 0);
            return 0;
        }
        $shipping_method = $carrier->getShippingMethod();

        if (!$carrier->active) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        // Free fees if free carrier
        if ($carrier->is_free == 1) {
            Cache::store($cache_id, 0);
            return 0;
        }

        // Select carrier tax
        if ($use_tax && !Tax::excludeTaxeOption()) {
            $address = Address::initialize((int)$address_id);

            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                // With PS_ATCP_SHIPWRAP, pre-tax price is deduced
                // from post tax price, so no $carrier_tax here
                // even though it sounds weird.
                $carrier_tax = 0;
            } else {
                $carrier_tax = $carrier->getTaxesRate($address);
            }
        }

        $configuration = Configuration::getMultiple(array(
            'PS_SHIPPING_FREE_PRICE',
            'PS_SHIPPING_HANDLING',
            'PS_SHIPPING_METHOD',
            'PS_SHIPPING_FREE_WEIGHT'
        ));

        // Free fees
        $free_fees_price = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE'])) {
            $free_fees_price = Tools::convertPrice(
                (float)$configuration['PS_SHIPPING_FREE_PRICE'],
                Currency::getCurrencyInstance((int)$this->id_currency)
            );
        }
        $orderTotalwithDiscounts = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalwithDiscounts >= (float)($free_fees_price) && (float)($free_fees_price) > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT'])
            && $this->getTotalWeight() >= (float)$configuration['PS_SHIPPING_FREE_WEIGHT']
            && (float)$configuration['PS_SHIPPING_FREE_WEIGHT'] > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        // Get shipping cost using correct method
        if ($carrier->range_behavior) {
            if (!isset($id_zone)) {
                // Get id zone
                if (isset($this->id_address_delivery)
                    && $this->id_address_delivery
                    && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)) {
                    $id_zone = Address::getZoneById((int)$this->id_address_delivery);
                } else {
                    $id_zone = (int)$default_country->id_zone;
                }
            }

            if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !Carrier::checkDeliveryPriceByWeight(
                $carrier->id,
                $this->getTotalWeight(),
                (int)$id_zone
            ))
                || (
                    $shipping_method == Carrier::SHIPPING_METHOD_PRICE && !Carrier::checkDeliveryPriceByPrice(
                        $carrier->id,
                        $total_package_without_shipping_tax_inc,
                        $id_zone,
                        (int)$this->id_currency
                    )
                )) {
                $shipping_cost += 0;
            } else {
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping_cost += $carrier->getDeliveryPriceByWeight(
                        $this->getTotalWeight($product_list),
                        $id_zone
                    );
                } else { // by price
                    $shipping_cost += $carrier->getDeliveryPriceByPrice(
                        $order_total,
                        $id_zone,
                        (int)$this->id_currency
                    );
                }
            }
        } else {
            if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
            } else {
                $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int)$this->id_currency);
            }
        }
        // Adding handling charges
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
            $shipping_cost += (float)$configuration['PS_SHIPPING_HANDLING'];
        }

        // Additional Shipping Cost per product
        foreach ($products as $product) {
            if (!$product['is_virtual']) {
                $shipping_cost += $product['additional_shipping_cost'] * $product['cart_quantity'];
            }
        }

        $shipping_cost = Tools::convertPrice($shipping_cost, Currency::getCurrencyInstance((int)$this->id_currency));

        //get external shipping cost from module
        if ($carrier->shipping_external) {
            $module_name = $carrier->external_module_name;

            /** @var CarrierModule $module */
            $module = Module::getInstanceByName($module_name);

            if (Validate::isLoadedObject($module)) {
                if (property_exists($module, 'id_carrier')) {
                    $module->id_carrier = $carrier->id;
                }
                if ($carrier->need_range) {
                    if (method_exists($module, 'getPackageShippingCost')) {
                        $shipping_cost = $module->getPackageShippingCost($this, $shipping_cost, $products);
                    } else {
                        $shipping_cost = $module->getOrderShippingCost($this, $shipping_cost);
                    }
                } else {
                    $shipping_cost = $module->getOrderShippingCostExternal($this);
                }

                // Check if carrier is available
                if ($shipping_cost === false) {
                    Cache::store($cache_id, false);
                    return false;
                }
            } else {
                Cache::store($cache_id, false);
                return false;
            }
        }

        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            if (!$use_tax) {
                // With PS_ATCP_SHIPWRAP, we deduce the pre-tax price from the post-tax
                // price. This is on purpose and required in Germany.
                $shipping_cost /= (1 + $this->getAverageProductsTaxRate());
            }
        } else {
            // Apply tax
            if ($use_tax && isset($carrier_tax)) {
                $shipping_cost *= 1 + ($carrier_tax / 100);
            }
        }

        if (Module::isInstalled('vitatiendashipping') && Module::isEnabled('vitatiendashipping')) {
            include_once dirname(__FILE__).'/../../modules/vitatiendashipping/classes/VitatiendaShippingCondition.php';
            $addressObj = new Address($this->id_address_delivery);
            if (isset($addressObj->id_country) && $addressObj->id_country) {
                $wkIdCountry = $addressObj->id_country;
                $wkIdZone = Country::getIdZone($wkIdCountry);
                $afsCondition = new VitatiendaShippingCondition();
                $resultByZone = $afsCondition->checkLocationForShippingByZone(
                    (new Carrier($id_carrier))->id_reference,
                    $wkIdZone
                );
                $conditionCountry = array();
                foreach ($resultByZone as $rs) {
                    $conditionCountry[] = $rs['id_country'];
                }
                if (in_array($wkIdCountry, $conditionCountry)) {
                    $result = $afsCondition->checkLocationForShipping(
                        (new Carrier($id_carrier))->id_reference,
                        $wkIdZone,
                        $wkIdCountry
                    );
                } else {
                    $result = $afsCondition->checkLocationForShipping(
                        (new Carrier($id_carrier))->id_reference,
                        $wkIdZone,
                        0
                    );
                }
                $productTotal = 0;
                $productWeight = 0;
                foreach ($this->getProducts() as $presentCart) {
                    if (isset($presentCart['total_wt'])) {
                        $productTotal += $presentCart['total_wt'];
                    }
                    $productWeight += $presentCart['cart_quantity'] * $presentCart['weight'];
                }

                if ($result) {
                    if ($this->id_currency == $result['id_currency']) {
                        $priceToAfs = $result['shipping_price'];
                    } else {
                        $priceToAfsConverted = Tools::convertPriceFull(
                            $result['shipping_price'],
                            Currency::getCurrencyInstance((int) $result['id_currency']),
                            Currency::getCurrencyInstance((int) $this->id_currency)
                        );
                        $priceToAfs = (float)Tools::ps_round((float)$priceToAfsConverted, 2);
                    }

                    if ($priceToAfs <= $productTotal) {
                        $shipping_cost = 0;
                        if ($result['handling_charge'] == 1) {
                            $shipping_cost += (float)$configuration['PS_SHIPPING_HANDLING'];
                        }
                        if ($result['tax_inc'] == 1) {
                            $shipping_cost_for_tax = 0;
                            if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                                $shipping_cost_for_tax += $carrier->getDeliveryPriceByWeight(
                                    $this->getTotalWeight($product_list),
                                    $wkIdZone
                                );
                            } else { // by price
                                $shipping_cost_for_tax += $carrier->getDeliveryPriceByPrice(
                                    $order_total,
                                    $wkIdZone,
                                    (int)$this->id_currency
                                );
                            }
                            $addressData = Address::initialize((int)$address_id);
                            $carrierData = self::$_carriers[$id_carrier];
                            $get_carrier_tax = $carrierData->getTaxesRate($addressData);
                            $shipping_tax = $shipping_cost_for_tax * ($get_carrier_tax/100);
                            $shipping_cost += $shipping_tax;
                        }
                    }
                }
            }
        }
        $shipping_cost = (float)Tools::ps_round((float)$shipping_cost, 2);
        Cache::store($cache_id, $shipping_cost);

        return $shipping_cost;
    }
}
