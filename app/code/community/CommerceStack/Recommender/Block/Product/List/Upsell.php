<?php

class CommerceStack_Recommender_Block_Product_List_Upsell extends Mage_Catalog_Block_Product_List_Upsell
{
    protected function _prepareData()
    {
        $limit = Mage::getStoreConfig('recommender/relatedproducts/numberofupsellproducts');

        $product = $this->getProduct();
        
        // A bit of a hack, but return an empty collection if user selected 0 recommendations to show in config
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
            if (is_object($currentCategory))
            {
                $productCategory = $currentCategory;
            }

            if(is_null($currentCategory))
            {
                // This could be a recently viewed or a search page. Try to get category collection and arbitrarily use first
                /* @var $currentProduct Mage_Catalog_Model_Product */
                $currentProduct = Mage::registry('current_product');
                if (is_object($currentProduct))
                {
                    $currentCategory = $currentProduct->getCategoryCollection();
                    $productCategory = $currentCategory->getFirstItem();
                    $currentCategory = $productCategory;
                }
            }
            $useCategoryFilter = !is_null($currentCategory) && $constrainCategory;

            // Set link source to automated CommerceStack recommendations
            $linkModel = $product->getLinkInstance();
            $linkModel->useLinkSourceCommerceStack();

            $upsellSource = Mage::getStoreConfig('recommender/relatedproductsadvanced/upsellsource');
        }

        while($numRecsToGet > 0)
        {
            if($upsellSource == 'related')
            {
                $linkedItemCollection = $product->getRelatedProductCollection()
                    ->addAttributeToSelect('required_options')
                    ->setGroupBy()
                    ->setPositionOrder()
                    ->addStoreFilter();
            }
            else
            {
                $linkedItemCollection = $product->getCrossSellProductCollection()
                    ->setGroupBy()
                    ->setPositionOrder()
                    ->addStoreFilter();
            }

            //$linkedItemCollection->addAttributeToFilter('discontinued', 0); // uncomment to filter by attribute
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

            // Go up a category level for next iteration
            $currentCategory = $currentCategory->getParentCategory();
            if(is_null($currentCategory->getId())) break;
        }

        
        $this->_itemCollection = $unionLinkedItemCollection;

        /**
         * Updating collection with desired items
         */
        Mage::dispatchEvent('catalog_product_upsell', array(
            'product'       => $product,
            'collection'    => $this->_itemCollection,
            'limit'         => $this->getItemLimit()
        ));

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        // We need to reset the link source to manual here so as not to break
        // grouped products
        $linkModel->useLinkSourceManual();

        return $this;
    }
}