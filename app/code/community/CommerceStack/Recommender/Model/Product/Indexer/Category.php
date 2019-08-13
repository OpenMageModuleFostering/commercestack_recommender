<?php

class CommerceStack_Recommender_Model_Product_Indexer_Category extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Data key for matching result to be saved in
     */
    const EVENT_MATCH_RESULT_KEY = 'catalog_category_product_match_result';
    const EVENT_POST_RECOMMENDER_INDEX = 'after_reindex_process_commercestack_recommender';

    /**
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION
        ),
        Mage_Catalog_Model_Category::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        )
    );

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
     * Register data required by process in event object
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
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
    }

    /**
     * Process event
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {

    }


    /**
     * match whether the reindexing should be fired
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
//        $data = $event->getNewData();
//        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
//            return $data[self::EVENT_MATCH_RESULT_KEY];
//        }
//        $entity = $event->getEntity();
//        $result = true;
//        if (!($entity == Mage_Catalog_Model_Product::ENTITY || $entity == Mage_Catalog_Model_Category::ENTITY))
//        {
//            return;
//        }
//        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);
//        return $result;
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