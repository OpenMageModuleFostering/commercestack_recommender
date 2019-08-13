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
class CommerceStack_Recommender_Block_Catalog_Product_Edit_Tabs extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->addTab('commercestack_related', array(
            'label'     => Mage::helper('catalog')->__('Related Products (Automated)'),
            'url'       => Mage::helper("adminhtml")->getUrl('adminhtml/recommenderproduct/related', array('_current' => true)), //'commercestack_related' => true)),
            'class'     => 'ajax',
            'insertAfter' => 'related',
        ));

        // Upsell source is based on user-config
        $upsellSource = Mage::getStoreConfig('recommender/relatedproductsadvanced/upsellsource');
        $tabUrls = array('related' => 'adminhtml/recommenderproduct/related', 'crosssell' => 'adminhtml/recommenderproduct/crosssell', 'random' => '*/*/upsell');

        $this->addTab('commercestack_upsell', array(
            'label'     => Mage::helper('catalog')->__('Up-sells (Automated)'),
            'url'       => Mage::helper("adminhtml")->getUrl($tabUrls[$upsellSource], array('_current' => true)), //'commercestack_upsell' => true)),
            'class'     => 'ajax',
            'insertAfter' => 'upsell',
        ));

        $this->addTab('commercestack_crosssell', array(
            'label'     => Mage::helper('catalog')->__('Cross-sells (Automated)'),
            'url'       => Mage::helper("adminhtml")->getUrl('adminhtml/recommenderproduct/crosssell', array('_current' => true)), //'commercestack_crosssell' => true)),
            'class'     => 'ajax',
            'insertAfter' => 'crosssell',
        ));
    }

    public function addTab($tabId, $tab)
    {
        if(isset($tab['insertAfter']))
        {
            // Remove and remember every tab after the specified key
            $afterTabs = array();
            $afterKeyFound = false;
            foreach($this->_tabs as $key => $value)
            {
                if($afterKeyFound)
                {
                    $afterTabs[$key] = $value;
                    unset($this->_tabs[$key]);
                }

                if($key == $tab['insertAfter']) $afterKeyFound = true;
            }
        }

        parent::addTab($tabId, $tab);

        if(isset($tab['insertAfter']))
        {
            // Now that we've added our new tab, add the remembered tabs back into the internal array
            $this->_tabs += $afterTabs;
        }
    }
}