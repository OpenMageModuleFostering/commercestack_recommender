<?php

class CommerceStack_Recommender_Block_Cart_Crosssell extends Mage_Checkout_Block_Cart_Crosssell
{
    protected $_linkSource = array('useLinkSourceManual', 'useLinkSourceCommerceStack'); // from most to least authoritative

    protected function _getCollection($linkSource = null)
    {
        if(is_null($linkSource)) $linkSource = $this->_linkSource[0];

        $collection = Mage::getModel('catalog/product_link')->useCrossSellLinks()
            ->{$linkSource}()
            ->getProductCollection()
            ->setStoreId(Mage::app()->getStore()->getId())
            //->addAttributeToFilter('discontinued', 0) // uncomment to filter by attribute
            ->addStoreFilter();
        $this->_addProductAttributesAndPrices($collection);

        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

        return $collection;
    }

    public function getItems()
    {
        $items = $this->getData('items');
        if (is_null($items)) {
            $items = array();
            $ninProductIds = $this->_getCartProductIds();
            if ($ninProductIds) {
                $lastAdded = (int) $this->_getLastAddedProductId();
                if ($lastAdded) {
                    $collection = $this->_getCollection()
                        ->addProductFilter($lastAdded);
                    if (!empty($ninProductIds)) {
                        $collection->addExcludeProductFilter($ninProductIds);
                    }
                    $collection->setPositionOrder()->load();

                    foreach ($collection as $item) {
                        $ninProductIds[] = $item->getId();
                        $items[] = $item;
                    }
                }

                $limit = Mage::getStoreConfig('recommender/relatedproducts/numberofcrosssellproducts') - count($items);

                // A bit of a hack, but return an empty collection if user selected 0 recommendations to show in config
                if($limit < 1)
                {
                    $this->setData('items', $items);
                    return $items;
                }

                if (count($items) > $limit)
                {
                    $this->setData('items', $items);
                    return $items;
                }

                // Get last product added to cart and its first category
                //$constrainCategory = Mage::getStoreConfig('recommender/relatedproducts/constraincategory');
                $constrainCategory = true;

                if($constrainCategory)
                {
                    $lastProductAdded = null;
                    $cartProducts = $this->getQuote()->getAllItems();
                    $max = 0;
                    $lastItem = null;
                    foreach ($cartProducts as $item)
                    {
                        if ($item->getId() > $max)
                        {
                            $max = $item->getId();
                            $lastItem = $item;
                        }
                    }
                    if ($lastItem)
                    {
                        $lastProductAdded = $lastItem->getProduct();
                        $categoryCollection = $lastProductAdded->getCategoryCollection();
                        $category = $categoryCollection->getFirstItem();
                        //$constrainCategory = Mage::getStoreConfig('recommender/relatedproducts/constraincategory');
                        $constrainCategory = true;
                        $useCategoryFilter = !is_null($category) && $constrainCategory;
                    }
                }

                $unionLinkedItemCollection = null;
                foreach($this->_linkSource as $linkSource)
                {
                    $numRecsToGet = $limit;
                    if(!is_null($unionLinkedItemCollection))
                    {
                        $numRecsToGet = $limit - count($unionLinkedItemCollection);
                    }

                    while($numRecsToGet > 0)
                    {
                        if(!is_null($unionLinkedItemCollection))
                        {
                            $ninProductIds = array_merge($ninProductIds, $unionLinkedItemCollection->getAllIds());
                        }

                        $filterProductIds = array_merge($this->_getCartProductIds(), $this->_getCartProductIdsRel());
                        $collection = $this->_getCollection($linkSource)
                            ->addProductFilter($filterProductIds)
                            ->addExcludeProductFilter($ninProductIds)
                            ->setGroupBy()
                            ->setPositionOrder();

                        $collection->getSelect()->limit($numRecsToGet);

                        if($useCategoryFilter && $linkSource == 'useLinkSourceCommerceStack')
                        {
                            $collection->addCategoryFilter($category);
                        }

                        $collection->load();

                        if(is_null($unionLinkedItemCollection))
                        {
                            $unionLinkedItemCollection = $collection;
                        }
                        else
                        {
                            // Add new source linked items to existing union of linked items
                            foreach($collection as $linkedProduct)
                            {
                                $unionLinkedItemCollection->addItem($linkedProduct);
                                $numRecsToGet--;
                            }
                        }

                        //  We only want this while to apply to automated recs
                        if($linkSource == 'useLinkSourceManual') break;

                        // Go up a category level for next iteration
                        $category = $category->getParentCategory();
                        if(is_null($category->getId())) break;
                    }
                }
            }

            foreach(@$unionLinkedItemCollection as $item)
            {
                $items[] = $item;
            }

            $this->setData('items', $items);
        }
        return $items;
    }
}