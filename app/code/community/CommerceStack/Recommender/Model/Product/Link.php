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
class CommerceStack_Recommender_Model_Product_Link extends Mage_Catalog_Model_Product_Link
{
    const LINK_SOURCE_MANUAL  = 1;
    const LINK_SOURCE_COMMERCESTACK  = 2;
    
    protected $_recTypes = array(self::LINK_TYPE_CROSSSELL => array('marketbasket', 'rulesbasedcrosssell'),
                                self::LINK_TYPE_RELATED => array('alsoviewed', 'rulesbasedrelated'));
	protected $_linkSource = self::LINK_SOURCE_MANUAL;
	protected $_collectionAsXml;

    public function update()
    {
        $dataHelper = Mage::helper('recommender');

        foreach($this->_recTypes as $linkType => $rootNames)
        {
            $this->_linkType = $linkType;

            foreach($rootNames as $rootName)
            {
                $this->_rootName = $rootName;
                $xml = $dataHelper->getFromServer($rootName);

                if($xml && $xml != '')
                {
                    $this->_collectionAsXml = simplexml_load_string($xml);

                    $this->setHasDataChanges(true);
                    $this->_getResource()->saveByRef($this);
                }
            }
        }
    }
    
    // These arguments are all dummies to remain compatible with Varien_Object::toXml()
    public function toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag = false, $addCdata = true)
    {
        return $this->_collectionAsXml;
    }
    
    public function getRootName()
    {
        return $this->_rootName;
    }
    
    public function getLinkType()
    {
        return $this->_linkType;
    }
    
    public function useLinkSourceManual()
    {
        $this->_linkSource = self::LINK_SOURCE_MANUAL;
        return $this;
    }
    
    public function useLinkSourceCommerceStack()
    {
        $this->_linkSource = self::LINK_SOURCE_COMMERCESTACK;
        return $this;
    }
    
    public function isLinkSourceManual()
    {
        if($this->_linkSource == self::LINK_SOURCE_MANUAL) return true;
        return false;
    }
    
    public function isLinkSourceCommerceStack()
    {
        if($this->_linkSource == self::LINK_SOURCE_COMMERCESTACK) return true;
        return false;
    }

    public function updateFromXml($xml, $linkType, $rootName)
    {
        $this->_linkType = $linkType;
        $this->_rootName = $rootName;
        $this->_collectionAsXml = $xml;
        $this->setHasDataChanges(true);
        $this->_getResource()->saveByRef($this);
    }
}