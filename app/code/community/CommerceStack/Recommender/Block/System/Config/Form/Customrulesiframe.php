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
class CommerceStack_Recommender_Block_System_Config_Form_Customrulesiframe extends Mage_Adminhtml_Block_System_Config_Form_Field
{  
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $server = Mage::getModel('csapiclient/server');
        $url = $server->base_url . 'customrules';

        // This will provision an account if none exists so the client should
        // have an account the first time the config page is loaded.
        $account = Mage::getModel('csapiclient/account');
        $url = $account->appendAuthToUri($url);

        try
        {
            $style = $server->get('/customrules/style');
        }
        catch(Exception $e)
        {
            // Credentials are probably messed up. Don't throw an exception here
            // so that user can see Api User value under Account for support
        }

        return '<iframe id="recommender_customrules_iframe" scrolling="no" src="' . $url . '" style="' . trim($style) . '"></iframe>';

    }
}