<?php

class CommerceStack_Recommender_Model_Category_Indexer_Product extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Data key for matching result to be saved in
     */
    const EVENT_MATCH_RESULT_KEY = 'recommender_category_product_match_result';
    const EVENT_POST_RECOMMENDER_INDEX = 'after_reindex_process_commercestack_recommender';

    /**
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            //Mage_Index_Model_Event::TYPE_MASS_ACTION
        ),
        Mage_Catalog_Model_Category::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Core_Model_Store::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Core_Model_Store_Group::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Catalog_Model_Convert_Adapter_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        )
    );

    /**
     * Initialize resource
     */
    protected function _construct()
    {
        $this->_init('recommender/category_indexer_product');
    }

    /**
     * Retrieve Indexer name
     * @return string
     */
    public function getName()
    {
        return 'Related Products Manager';
    }

    /**
     * Retrieve Indexer description
     * @return string
     */
    public function getDescription()
    {
        return 'Refresh automatic related products and cross-sells';
    }

    /**
     * match whether the reindexing should be fired
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $data      = $event->getNewData();
        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
            return $data[self::EVENT_MATCH_RESULT_KEY];
        }

        $entity = $event->getEntity();
        if ($entity == Mage_Core_Model_Store::ENTITY) {
            $store = $event->getDataObject();
            if ($store && ($store->isObjectNew() || $store->dataHasChangedFor('group_id'))) {
                $result = true;
            } else {
                $result = false;
            }
        } elseif ($entity == Mage_Core_Model_Store_Group::ENTITY) {
            $storeGroup = $event->getDataObject();
            $hasDataChanges = $storeGroup && ($storeGroup->dataHasChangedFor('root_category_id')
                    || $storeGroup->dataHasChangedFor('website_id'));
            if ($storeGroup && !$storeGroup->isObjectNew() && $hasDataChanges) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = parent::matchEvent($event);
        }

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

        return $result;
    }

    /**
     * Register data required by process in event object
     * Check if category ids was changed
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, true);
        $entity = $event->getEntity();
        switch ($entity)
        {
            case Mage_Catalog_Model_Product::ENTITY:
                $this->_registerProductEvent($event);
                break;

            case Mage_Catalog_Model_Category::ENTITY:
                $this->_registerCategoryEvent($event);
                break;
        }
        return $this;
    }

    /**
     * Register data required by process in event object
     * @param Mage_Index_Model_Event $event
     */
//    protected function _registerEvent(Mage_Index_Model_Event $event)
//    {
//        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, true);
//        $process = $event->getProcess();
//
//        try
//        {
//            /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
//            $dataHelper = Mage::helper('recommender');
//            $dataHelper->requestUpdate(3);
//        }
//        catch(Exception $e)
//        {
//            // Manual indexing required
//            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
//        }
//
//        return $this;
//    }

    /**
     * Register event data during product save process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerProductEvent(Mage_Index_Model_Event $event)
    {
        $eventType = $event->getType();
        if ($eventType == Mage_Index_Model_Event::TYPE_SAVE)
        {
            $product = $event->getDataObject();
            /**
             * Check if product categories data was changed
             */
            if ($product->getIsChangedCategories())
            {
                $event->addNewData('category_ids', $product->getCategoryIds());
            }
        }
//        else if ($eventType == Mage_Index_Model_Event::TYPE_MASS_ACTION) {
//            /* @var $actionObject Varien_Object */
//            $actionObject = $event->getDataObject();
//            $attributes   = array('status', 'visibility');
//            $rebuildIndex = false;
//
//            // check if attributes changed
//            $attrData = $actionObject->getAttributesData();
//            if (is_array($attrData)) {
//                foreach ($attributes as $attributeCode) {
//                    if (array_key_exists($attributeCode, $attrData)) {
//                        $rebuildIndex = true;
//                        break;
//                    }
//                }
//            }
//
//            // check changed websites
//            if ($actionObject->getWebsiteIds()) {
//                $rebuildIndex = true;
//            }
//
//            // register affected products
//            if ($rebuildIndex) {
//                $event->addNewData('product_ids', $actionObject->getProductIds());
//            }
//        }
    }

    /**
     * Register event data during category save process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerCategoryEvent(Mage_Index_Model_Event $event)
    {
        $category = $event->getDataObject();
        /**
         * Check if product categories data was changed
         */
        if ($category->getIsChangedProductList())
        {
            $event->addNewData('affected_product_ids', $category->getAffectedProductIds());
        }
        else
        {
            $event->addNewData('recommender_category_product_skip_call_event_handler', true);
        }
        /**
         * Check if category has another affected category ids (category move result)
         */
//        if ($category->getAffectedCategoryIds())
//        {
//            $event->addNewData('affected_category_ids', $category->getAffectedCategoryIds());
//        }

    }

    /**
     * Process event
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (!empty($data['recommender_category_product_reindex_all'])) {
            $this->reindexAll();
        }
        if (empty($data['recommender_category_product_skip_call_event_handler'])) {
            $this->callEventHandler($event);
        }
    }


    /**
     * Rebuild all index data
     */
    public function reindexAll()
    {
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        $dataHelper->doClientDrivenUpdate();
    }

    public function setStatus($status)
    {
        $process = Mage::getSingleton('index/indexer')->getProcessByCode('commercestack_recommender');
        switch($status)
        {
            case "STATUS_PENDING":
                $process->changeStatus(Mage_Index_Model_Process::STATUS_PENDING);
                break;
            case "STATUS_RUNNING":
                $process->changeStatus(Mage_Index_Model_Process::STATUS_RUNNING);
                break;
            case "STATUS_REQUIRE_REINDEX":
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                break;
        }
    }
}