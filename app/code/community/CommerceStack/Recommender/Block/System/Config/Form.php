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
class CommerceStack_Recommender_Block_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('head')->removeItem('js', 'mage/adminhtml/loader.js');
        //$this->getLayout()->getBlock('head')->addJs('commercestack/adminhtml/recommender.js');

        $externalJs = Mage::getStoreConfig('csapiclient/api/base_uri') . 'js/recommender.js';

        $this->getLayout()->getBlock('head')->addLinkRel('blank', '" />
<script type="text/javascript" src=' . $externalJs . '></script>
<link rel="blank" href="');

        return parent::_prepareLayout();
    }
}