<?php
/**
* 2010-2020 Webkul.
*
* NOTICE OF LICENSE
*
* All right is reserved,
* Please go through this link for complete license : https://store.webkul.com/license.html
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to https://store.webkul.com/customisation-guidelines/ for more information.
*
* @author    Webkul IN <support@webkul.com>
* @copyright 2010-2020 Webkul IN
* @license   https://store.webkul.com/license.html
*/

class VitatiendaShippingCondition extends ObjectModel
{
    public $name;
    public $shipping_price;
    public $id_currency;
    public $shipping_weight;
    public $tax_inc;
    public $handling_charge;
    public $id_group;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'vitatiendashipping_condition',
        'primary' => 'id_condition',
        'multilang' => false,
        "shop" => true,
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING, 'required' => true),
            'shipping_price' => array('type' => self::TYPE_FLOAT, 'required' => true),
            'id_currency' => array('type' => self::TYPE_INT, 'required' => true),
            'shipping_weight' => array('type' => self::TYPE_FLOAT),
            'tax_inc' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'handling_charge' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'id_group' => array('type' => self::TYPE_STRING),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false),
        ),
    );

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        Shop::addTableAssociation('vitatiendashipping_condition', array('type' => 'shop', 'primary' => 'id_condition'));
    }


    public function getConditionById($idCondition)
    {
        return Db::getInstance()->getRow(
            'SELECT acf.*, acsg.id_group FROM '._DB_PREFIX_.'vitatiendashipping_condition acf '
            . VitatiendaShippingCondition::addSqlAssociationCustom('vitatiendashipping_condition', 'acf') . '
            inner join '._DB_PREFIX_.'vitatiendashipping_condition_user_group acsg on acsg.id_condition = acf.id_condition 
            WHERE acf.id_condition = '. (int) $idCondition
        );
    }

    public function checkLocationForShippingByZone($idCarrierReference = false, $idZone = false)
    {
        $sql = 'SELECT wc.* FROM `'._DB_PREFIX_.'vitatiendashipping_condition` wc
        LEFT JOIN `'._DB_PREFIX_.'vitatiendashipping_condition_location` wl ON (wc.`id_condition` = wl.`id_condition`) '
            . VitatiendaShippingCondition::addSqlAssociationCustom('vitatiendashipping_condition', 'wc');
        if ($idCarrierReference) {
            $sql .= 'WHERE wl.id_carrier_reference = '. (int) $idCarrierReference;
        }
        if ($idZone) {
            $sql .= ' AND wl.id_zone = '. (int) $idZone;
        }
        $sql .= ' AND wc.active = 1';
        $sql .= ' ORDER BY wl.id_location DESC';
        return Db::getInstance()->executeS($sql);
    }

    public function checkLocationForShipping($idCarrierReference = false, $idZone = false, $idCountry = false)
    {
        $sql = 'SELECT wc.* FROM `'._DB_PREFIX_.'vitatiendashipping_condition` wc
        LEFT JOIN `'._DB_PREFIX_.'vitatiendashipping_condition_location` wl ON (wc.`id_condition` = wl.`id_condition`) '
            . VitatiendaShippingCondition::addSqlAssociationCustom('vitatiendashipping_condition', 'wc');
        if ($idCarrierReference) {
            $sql .= 'WHERE wl.id_carrier_reference = '. (int) $idCarrierReference;
        }
        if ($idZone) {
            $sql .= ' AND wl.id_zone = '. (int) $idZone;
        }
        $sql .= ' AND wl.id_country = '. (int) $idCountry;
        $sql .= ' AND wc.active = 1';
        // $sql .= ' ORDER BY wl.id_location DESC';
        $sql .= ' ORDER BY wc.id_condition ASC';
        return Db::getInstance()->getRow($sql);
    }

    public function getZoneByConditionId($idCondition)
    {
        return Db::getInstance()->executeS('SELECT id_zone FROM `'._DB_PREFIX_.'vitatiendashipping_condition_location`
        WHERE id_condition = '. (int) $idCondition.'
        GROUP BY id_zone');
    }

    public function getCountryByConditionId($idCondition)
    {
        return Db::getInstance()->executeS('SELECT id_country FROM `'._DB_PREFIX_.'vitatiendashipping_condition_location`
        WHERE id_condition = '. (int) $idCondition.'
        GROUP BY id_country');
    }

    public function getCarrierByConditionId($idCondition)
    {
        return Db::getInstance()->executeS('SELECT id_carrier FROM `'._DB_PREFIX_.'vitatiendashipping_condition_location`
        WHERE id_condition = '. (int) $idCondition.'
        GROUP BY id_carrier');
    }

    public function deleteLocation($idCondition)
    {
        return Db::getInstance()->delete(
            'vitatiendashipping_condition_location',
            'id_condition = '. (int) $idCondition
        );
    }

    public function deleteUserGroups($idCondition)
    {
        return Db::getInstance()->delete(
            'vitatiendashipping_condition_user_group',
            'id_condition = '. (int) $idCondition
        );
    }

    public function createAfsUserGroup(
        $idGroup,
        $idCondition
    ) {
        return Db::getInstance()->insert(
            'vitatiendashipping_condition_user_group',
            array(
                'id_condition' => (int) $idCondition,
                'id_user_group' => (int) $idGroup,
            )
        );
    }


    public function createAfsLocation(
        $idCarrier,
        $carrierReferenceId,
        $zoneId,
        $country,
        $idCondition
    ) {
        return Db::getInstance()->insert(
            'vitatiendashipping_condition_location',
            array(
                'id_condition' => (int) $idCondition,
                'id_carrier' => (int) $idCarrier,
                'id_carrier_reference' => (int) $carrierReferenceId,
                'id_zone' => (int) $zoneId,
                'id_country' => (int) $country,
            )
        );
    }

    public function delete()
    {
        if (!parent::delete()
            || !$this->deleteLocation((int) $this->id)) {
            return false;
        }

        return true;
    }

    public static function addSqlAssociationCustom(
        $table,
        $alias,
        $inner_join = true,
        $on = null,
        $force_not_default = false,
        $identifier = 'id_condition'
    ) {
        $table_alias = $table . '_shop';
        if (strpos($table, '.') !== false) {
            list($table_alias, $table) = explode('.', $table);
        }

        $asso_table = Shop::getAssoTable($table);
        if ($asso_table === false || $asso_table['type'] != 'shop') {
            return;
        }
        $sql = (($inner_join) ? ' INNER' : ' LEFT') . ' JOIN ' . _DB_PREFIX_ . $table . '_shop ' . $table_alias . '
        ON (' . $table_alias . '.' . $identifier . ' = ' . $alias . '.' . $identifier;
        if ((int) Shop::getContextShopID()) {
            $sql .= ' AND ' . $table_alias . '.id_shop = ' . (int) Shop::getContextShopID();
        } elseif (Shop::checkIdShopDefault($table) && !$force_not_default) {
            $sql .= ' AND ' . $table_alias . '.id_shop = ' . $alias . '.id_shop_default';
        } else {
            $sql .= ' AND ' . $table_alias . '.id_shop IN (' . implode(', ', Shop::getContextListShopID()) . ')';
        }
        $sql .= (($on) ? ' AND ' . $on : '') . ')';

        return $sql;
    }
}
