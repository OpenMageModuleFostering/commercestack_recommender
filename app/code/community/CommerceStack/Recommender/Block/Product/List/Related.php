<?php
/**
 * CommerceStack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to help@commercestack.com so we can send you a copy immediately.
 *
 * @category    CommerceStack
 * @package     CommerceStack_Recommender
 * @copyright   Copyright (c) 2013-2016 CommerceStack, Inc. (http://www.commercestack.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 **/
class CommerceStack_Recommender_Block_Product_List_Related extends Mage_Catalog_Block_Product_List_Related
{
    protected function _prepareData()
    {
        $product = $this->getProduct();

        // A bit of a hack, but return an empty collection if user selected 0 recommendations to show in config
        $limit = Mage::getStoreConfig('recommender/relatedproducts/numberofrelatedproducts');
        if($limit < 1)
        {
            $this->_itemCollection = $product->getRelatedProductCollection();
            $this->_itemCollection->load();
            $this->_itemCollection->clear();
            return $this;
        }

        // Get manual links
        // Set link source to automated CommerceStack recommendations
        $linkModel = $product->getLinkInstance();
        $linkModel->useLinkSourceManual();
        parent::_prepareData();
        $unionLinkedItemCollection = $this->_itemCollection;

        $numRecsToGet = $limit;
        if(!is_null($unionLinkedItemCollection))
        {
            $numRecsToGet = $limit - count($unionLinkedItemCollection);
        }

        if($numRecsToGet > 0)
        {
            // Figure out if we should use a category filter
            //$constrainCategory = Mage::getStoreConfig('recommender/relatedproducts/constraincategory');
            $constrainCategory = true;
            $currentCategory = Mage::registry('current_category');

            if(is_null($currentCategory))
            {
                // This could be a recently viewed or a search page. Try to get category collection and arbitrarily use first
                /* @var $currentProduct Mage_Catalog_Model_Product */
                $currentProduct = Mage::registry('current_product');
                if (is_object($currentProduct))
                {
                    $currentCategory = $currentProduct->getCategoryCollection()->getFirstItem();
                }
            }
            $useCategoryFilter = !is_null($currentCategory) && $constrainCategory;

            // Set link source to automated CommerceStack recommendations
            $linkModel = $product->getLinkInstance();
            $linkModel->useLinkSourceCommerceStack();
        }

        while($numRecsToGet > 0)
        {
            $linkedItemCollection = $product->getRelatedProductCollection()
                ->addAttributeToSelect('required_options')
                ->setGroupBy()
                ->setPositionOrder()
                //->addAttributeToFilter('discontinued', 0) // uncomment to filter by attribute
                ->addStoreFilter();

            $linkedItemCollection->getSelect()->limit($numRecsToGet);

            if($useCategoryFilter)
            {
                $linkedItemCollection->addCategoryFilter($currentCategory);
            }

            if(!is_null($unionLinkedItemCollection))
            {
                $linkedItemCollection->addExcludeProductFilter($unionLinkedItemCollection->getAllIds());
            }

            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($linkedItemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($linkedItemCollection);

            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($linkedItemCollection);
            /**
            Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collection);
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
             */

            $linkedItemCollection->load();

            // Add new source linked items to existing union of linked items
            if(is_null($unionLinkedItemCollection))
            {
                $unionLinkedItemCollection = $linkedItemCollection;
            }
            else
            {
                foreach($linkedItemCollection as $linkedProduct)
                {
                    $unionLinkedItemCollection->addItem($linkedProduct);
                }
            }

            if(!is_null($unionLinkedItemCollection))
            {
                $numRecsToGet = $limit - count($unionLinkedItemCollection);
            }

            if(!$useCategoryFilter) break;

            // Go up a category level for next iteration
            $currentCategory = $currentCategory->getParentCategory();
            if(is_null($currentCategory->getId())) break;
        }

        //$this->_itemCollection = $unionLinkedItemCollection;

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }
}