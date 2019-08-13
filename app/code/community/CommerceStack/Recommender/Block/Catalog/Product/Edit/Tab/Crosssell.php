<?php
class CommerceStack_Recommender_Block_Catalog_Product_Edit_Tab_Crosssell extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Crosssell
{
    public function isReadonly()
    {
        return true;
    }
    
    public function getSelectedCrossSellProducts()
    {
        Mage::registry('current_product')->getLinkInstance()->useLinkSourceCommerceStack();
        $currentProduct = Mage::registry('current_product');
        $products = array();
        
        // If this product has a parent, use that instead since we do not produce recommendations
        // for children
        $configurableProductModel = Mage::getModel('catalog/product_type_configurable');
        $parentIdArray = $configurableProductModel->getParentIdsByChild($currentProduct->getId());
        if(count($parentIdArray) > 0)
        {
            $currentProduct = Mage::getModel('catalog/product')->load($parentIdArray[0]);
        }
        
        $products = array();
        foreach ($currentProduct->getCrossSellProducts() as $product) {
            $products[$product->getId()] = array('position' => $product->getPosition());
        }
        return $products;
    }
    
    protected function _prepareCollection()
    {
        $currentProduct = $this->_getProduct();

        // If this product has a parent, use that instead since we do not produce recommendations
        // for children
        $configurableProductModel = Mage::getModel('catalog/product_type_configurable');
        $parentIdArray = $configurableProductModel->getParentIdsByChild($currentProduct->getId());
        if(count($parentIdArray) > 0)
        {
            $currentProduct = Mage::getModel('catalog/product')->load($parentIdArray[0]);
        }
        
        $collection = Mage::getModel('catalog/product_link')->useCrossSellLinks()
            ->useLinkSourceCommerceStack()
            ->getProductCollection()
            ->setProduct($currentProduct)
            ->setPositionOrder()
            ->addAttributeToSelect('*');

        $collection->addStoreFilter($this->getRequest()->getParam('store'));

        if ($this->isReadonly()) {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = array(0);
                $emptyText = 'Not enough page view/order data yet for product. Random cross-sells will be shown.';

                $account = Mage::getModel('csapiclient/account');

                try
                {
                    $subs = $account->getSubscriptions();

                    if(!is_null($subs) && isset($subs['rpm']))
                    {
                        if(isset($subs['rpm_plan_required']))
                        {
                            if($subs['rpm'] < $subs['rpm_plan_required'])
                            {
                                $emptyText = "Random cross-sells are currently showing. Please <a href='" .
                                    Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/recommender") .
                                    "#recommender_account-head'>upgrade to Related Products Manager ";

                                if($subs['rpm_plan_required'] == 3)
                                {
                                    $emptyText .= "Pro";
                                }
                                else
                                {
                                    $emptyText .= "Basic";
                                }

                                $emptyText .= "</a> to show smart recommendations.";
                            }
                        }
                        else
                        {
                            $emptyText = "Random cross-sells are currently showing because no smart recommendations have been generated. Please click Update Related Products in <a href='";
                            $emptyText .= Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/recommender");
                            $emptyText .= "'>Configuration > Related Products Manager</a> to create smart recommendations.";

                        }
                    }
                }
                catch(Exception $e)
                {
                    // Server is probably having trouble
                    $emptyText = "Random cross-sells are currently being shown.";
                }

                $this->_emptyText = Mage::helper('adminhtml')->__($emptyText);
            }
            $collection->addFieldToFilter('entity_id', array('in'=>$productIds));
        }

        $this->setCollection($collection);

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn('count', array(
            'header'    => Mage::helper('customer')->__('Times Bought Together'),
            'index'     => 'count'
        ));

        $col = $this->getColumn('position');
        $col->setEditable(false);
    }
}