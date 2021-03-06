<?php

namespace WooCommerceCustobar\DataType;

use WooCommerceCustobar\FieldsMap;
use WooCommerceCustobar\DataSource\Sale;

defined('ABSPATH') or exit;

/**
 * Class CustobarSale
 *
 * Check field descriptions here: https://www.custobar.com/api/docs/sales/
 *
 * @package WooCommerceCustobar\DataType
 */
class CustobarSale extends AbstractCustobarDataType
{
    CONST SALE_EXTERNAL_ID = 'sale_external_id';
    CONST SALE_DATE = 'sale_date';
    CONST SALE_CUSTOMER_ID = 'sale_customer_id';
    CONST SALE_DISCOUNT = 'sale_discount';
    CONST SALE_PAYMENT_METHOD = 'sale_payment_method';
    CONST SALE_SHIPPING = 'sale_shipping';
    CONST SALE_SHOP_ID = 'sale_shop_id';
    CONST SALE_STATE = 'sale_state';
    CONST SALE_TOTAL = 'sale_total';
    CONST EXTERNAL_ID = 'external_id';
    CONST PRODUCT_ID = 'product_id';
    CONST QUANTITY = 'quantity';
    CONST UNIT_PRICE = 'unit_price';
    CONST DISCOUNT = 'discount';
    CONST TOTAL = 'total';
    CONST SALE_PHONE_NUMBER = 'sale_phone_number';
    CONST SALE_EMAIL = 'sale_email';

    /**
     * Maps WC_Order and WC_Order_Item_Product objects' properties to match
     * the ones used in Custobar.
     *
     * @param \WC_Order              $order
     * @param \WC_Order_Item_Product $order_item
     */
    public function __construct($order, $order_item)
    {
        parent::__construct();
        
        $this->dataSource = new Sale($order, $order_item);
    }

    public static function getFieldsMap() {
        return FieldsMap::getSaleFields();
    }
}
