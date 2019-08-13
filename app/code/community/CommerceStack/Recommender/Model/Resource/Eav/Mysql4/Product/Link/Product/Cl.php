<?php
// Changed class name to stay under 100 characters and avoid Magento Connect bug where files with paths
// over 100 characters are dropped as directories on some flavors of Linux.

//class CommerceStack_Recommender_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
class CommerceStack_Recommender_Model_Resource_Eav_Mysql4_Product_Link_Product_Cl
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
{  
	/**
     * Join linked products and their attributes
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
     */
    protected function _joinLinks()
    {
        if($this->getLinkModel()->isLinkSourceManual()) return parent::_joinLinks();

        $select  = $this->getSelect();
        $adapter = $select->getAdapter();

        $joinCondition = array(
            'links.linked_product_id = e.entity_id',
            $adapter->quoteInto('links.link_type_id = ?', $this->_linkTypeId)
        );
        $joinType = 'join';
        if ($this->getProduct() && $this->getProduct()->getId()) {
            $productId = $this->getProduct()->getId();
            if ($this->_isStrongMode) {
                $this->getSelect()->where('links.product_id = ?', (int)$productId);
            } else {
                $joinType = 'joinLeft';
                $joinCondition[] = $adapter->quoteInto('links.product_id = ?', $productId);
            }
            $this->addFieldToFilter('entity_id', array('neq' => $productId));
        } else if ($this->_isStrongMode) {
            $this->addFieldToFilter('entity_id', array('eq' => -1));
        }
        if($this->_hasLinkFilter) {
            $select->$joinType(
                array('links' => $this->getTable('recommender/product_link')),
                implode(' AND ', $joinCondition),
                array('link_id', 'position')
            );
            // The only attribute in this model is position which we override anyway.
            // $this->joinAttributes();
        }
        if(!is_null($this->getCategoryFilter()) && !is_null($this->getCategoryFilter()->getId()))
        {
            $this->getSelect()->where('links.category_id IS NULL OR links.category_id = ?', (int)$this->getCategoryFilter()->getId());
        }

        return $this;
    }
    
    public function getSize()
    {
        // 2nd part of OR condition added to fix 20 item limit problem in the admin
        if(is_null($this->isLoaded()) || $this->getLinkModel()->isLinkSourceManual())
        {
            // We haven't loaded the collection yet (probably Admin page). Get size
            // by querying the DB in the usual way (this gets only the size of manually defined links)
            return parent::getSize();
        }
        else 
        {
            $this->_totalRecords = count(array_keys($this->getItems()));
        }
        return intval($this->_totalRecords);
    }
    
    public function getAllIds($limit=null, $offset=null)
    {
        // $limit and $offset are ignored and are for compatibility with parent class only
        return array_keys($this->getItems());
    }
    
    public function clear()
    {
        $this->_items = array();
    }

    public function addCategoryFilter(Mage_Catalog_Model_Category $category)
    {
        $this->_categoryFilter = $category;
    }

    public function getCategoryFilter()
    {
        if(!isset($this->_categoryFilter)) return null;
        return $this->_categoryFilter;
    }
}