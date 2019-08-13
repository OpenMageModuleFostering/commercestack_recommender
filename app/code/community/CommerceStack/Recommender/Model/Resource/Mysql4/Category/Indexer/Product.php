<?php

/**
 * Resource model for category product indexer
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class CommerceStack_Recommender_Model_Resource_Mysql4_Category_Indexer_Product extends Mage_Index_Model_Resource_Abstract
{
    /**
     * Category table
     *
     * @var string
     */
    protected $_categoryTable;

    /**
     * Category product table
     *
     * @var string
     */
    protected $_categoryProductTable;

    protected function _construct()
    {
        $this->_init('recommender/product_link', 'link_id');
        $this->_categoryTable        = $this->getTable('catalog/category');
        $this->_categoryProductTable = $this->getTable('catalog/category_product');
    }

    public function catalogProductSave(Mage_Index_Model_Event $event)
    {
        $productId = $event->getEntityPk();
        $data      = $event->getNewData();

        /**
         * Check if category ids were updated
         */
        if (!isset($data['category_ids'])) {
            return $this;
        }

        $this->_refreshProductLinks($productId);

        return $this;
    }

    public function catalogCategorySave(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        if(!isset($data['affected_product_ids']) || count($data['affected_product_ids']) < 1)
        {
            return $this;
        }

        foreach($data['affected_product_ids'] as $productId)
        {
            $this->_refreshProductLinks($productId);
        }

        return $this;
    }

    protected function _refreshProductLinks($productId)
    {
        /**
         * Select relations to categories
         */
        $select = $this->_getWriteAdapter()->select()
            ->from(array('cp' => $this->_categoryProductTable), 'category_id')
            ->joinInner(array('ce' => $this->_categoryTable), 'ce.entity_id=cp.category_id', 'path')
            ->where('cp.product_id=:product_id');

        /**
         * Get information about current product categories
         */
        $categories = $this->_getWriteAdapter()->fetchPairs($select, array('product_id' => $productId));
        $categoryIds = array();
        $allCategoryIds = array();

        foreach ($categories as $id=>$path) {
            $categoryIds[]  = $id;
            $allCategoryIds = array_merge($allCategoryIds, explode('/', $path));
        }
        $allCategoryIds = array_map('intval', array_unique($allCategoryIds));
        //$allCategoryIds = array_map('intval', array_diff($allCategoryIds, $categoryIds));

        /**
         * Delete previous index data (Zend makes doing deletes on multiple OR where clauses difficult so we do two.
         */
        if(count($allCategoryIds))
        {
            // There are some remaining or new categories so delete the links that aren't these remaining or new
            // categories (that is, delete links that were just removed)
            $this->_getWriteAdapter()->delete(
                $this->getMainTable(),
                array('product_id = ?' => $productId, 'category_id NOT IN (?)' => $allCategoryIds));

            $this->_getWriteAdapter()->delete(
                $this->getMainTable(),
                array('linked_product_id = ?' => $productId, 'category_id NOT IN (?)' => $allCategoryIds));
        }
        else
        {
            // There are no categories left. Delete all links
            $this->_getWriteAdapter()->delete(
                $this->getMainTable(),
                array('product_id = ?' => $productId));

            $this->_getWriteAdapter()->delete(
                $this->getMainTable(),
                array('linked_product_id = ?' => $productId));

            return $this;
        }

        // Identify new categories (that is, categoryIds for this product where there are no linked products)
        $select = $this->_getReadAdapter()->select()->distinct()
            ->from($this->getMainTable(), 'category_id')
            ->where('product_id = ?', $productId);

        $stmt = $select->query();
        $result = $stmt->fetchAll();

        $existingCategoryIds = array();
        foreach($result as $row)
        {
            $existingCategoryIds[] = $row['category_id'];
        }

        $neededCategoryIds = array_diff($allCategoryIds, $existingCategoryIds);

        foreach($neededCategoryIds as $neededCategoryId)
        {
            // Grab products from same categories
            $neededCategoryId = (int)$neededCategoryId;
            $productId = (int)$productId;

            $select = $this->_getReadAdapter()->select()->distinct()
                ->from($this->getMainTable(), 'linked_product_id')
                ->where('category_id = ?', $neededCategoryId)
                ->limit(10);

            $result = $this->_getReadAdapter()->fetchCol($select);

            // Sadly, MySQL will lock if we do an INSERT INTO... SELECT above and Zend DB has no support for writing
            // multiple rows so we must do n INSERTS here for each link_type_id;
            $i = 1;
            foreach($result as $linkedProductId)
            {
                $newLinkedProductRow = array(
                    'product_id'        => $productId,
                    'category_id'       => $neededCategoryId,
                    'linked_product_id' => (int)$linkedProductId,
                    'position'          => $i++,
                    'link_type_id'      => Mage_Catalog_Model_Product_Link::LINK_TYPE_RELATED
                );

                $this->_getWriteAdapter()->insert($this->getMainTable(), $newLinkedProductRow);

                $newLinkedProductRow['link_type_id'] = Mage_Catalog_Model_Product_Link::LINK_TYPE_CROSSSELL;
                $this->_getWriteAdapter()->insert($this->getMainTable(), $newLinkedProductRow);
            }
        }
    }
}
