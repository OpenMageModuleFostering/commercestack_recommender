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
class CommerceStack_Recommender_Block_Catalog_Product_Edit_Tab_Related extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Related
{
    public function isReadonly()
    {
        return true;
    }
    
    public function getSelectedRelatedProducts()
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

        foreach ($currentProduct->getRelatedProducts() as $product) {
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
        
        $collection = Mage::getModel('catalog/product_link')->useRelatedLinks()
            ->useLinkSourceCommerceStack()
            ->getProductCollection()
            ->setProduct($currentProduct)
            ->addAttributeToSelect('*');

        $collection->addStoreFilter($this->getRequest()->getParam('store'));
            
        if ($this->isReadonly()) {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = array(0);
                $emptyText = 'Not enough page view/order data yet for product. Random related products will be shown.';

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
                                $emptyText = "Random related products are currently showing. Please <a href='" .
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
                            $emptyText = "Random related products are currently showing because no smart recommendations have been generated. Please click Update Related Products in <a href='";
                            $emptyText .= Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/recommender");
                            $emptyText .= "'>Configuration > Related Products Manager</a> to create smart recommendations.";

                        }
                    }
                }
                catch(Exception $e)
                {
                    // Server is probably having trouble
                    $emptyText = "Random related products are currently being shown.";
                }


                $this->_emptyText = Mage::helper('adminhtml')->__($emptyText);
            }
            $collection->addFieldToFilter('entity_id', array('in' => $productIds));
        }

        $this->setCollection($collection);
        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn('count', array(
            'header'    => Mage::helper('customer')->__('Times Viewed Together'),
            'index'     => 'count'
        ));

        $col = $this->getColumn('position');
        $col->setEditable(false);
    }
}