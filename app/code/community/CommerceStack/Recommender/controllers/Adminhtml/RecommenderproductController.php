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
include("Mage/Adminhtml/controllers/Catalog/ProductController.php");
class CommerceStack_Recommender_Adminhtml_RecommenderproductController extends Mage_Adminhtml_Catalog_ProductController
{
    public function relatedAction()
    {
        $this->_initProduct();
        $this->loadLayout();
        
        //$layout = $this->getLayout();
        //$block = $layout->getBlock('catalog.product.edit.tab.related');
        //$block->setProductsRelated($this->getRequest()->getPost('products_related', null));
        $this->renderLayout();
    }

    public function relatedCrosssell()
    {
        $this->_initProduct();
        $this->loadLayout();
        
        //$layout = $this->getLayout();
        //$block = $layout->getBlock('catalog.product.edit.tab.crosssell');
        //$block->setProductsRelated($this->getRequest()->getPost('products_related', null));
        $this->renderLayout();
    }

}