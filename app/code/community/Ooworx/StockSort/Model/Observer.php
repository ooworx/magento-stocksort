<?php
/**
   Copyright (C) 2015 - Ooworx

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
class Ooworx_StockSort_Model_Observer {

    public function catalogProductCollectionLoadBefore($observer) {
        // Check is activated
        if (Mage::getStoreConfig('cataloginventory/options/stocksort') == false) {
            return $this;
        }
        $collection = $observer->getCollection();
        // Join inventory table to get product state of stock
        $collection->getSelect()
            ->joinLeft(
                array('_inventory_table' => $collection->getTable('cataloginventory/stock_item')),
                "_inventory_table.product_id = e.entity_id",
                array('is_in_stock', 'manage_stock') // Select attribute for product
            );
        // Create is_outofstock attribute
        $collection->addExpressionAttributeToSelect('is_outofstock', '(CASE WHEN (((_inventory_table.use_config_manage_stock = 1) AND (_inventory_table.is_in_stock = 1)) OR  ((_inventory_table.use_config_manage_stock = 0) AND (1 - _inventory_table.manage_stock + _inventory_table.is_in_stock >= 1))) THEN 1 ELSE 0 END)', array());
        $collection->getSelect()->order('is_outofstock DESC'); // Set to DESC to put at end
        // Make sure on_top is the first order directive
        $order = $collection->getSelect()->getPart('order');
        array_unshift($order, array_pop($order));
        $collection->getSelect()->setPart('order', $order);
        return $this;
        // End
    }

}
