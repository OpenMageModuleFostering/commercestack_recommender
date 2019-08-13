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
 * @package     CommerceStack_CsNotification
 * @copyright   Copyright (c) 2013-2016 CommerceStack, Inc. (http://www.commercestack.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class CommerceStack_CsNotification_Model_AdminNotification_Feed extends Mage_AdminNotification_Model_Feed
{
    public function getFeedData()
    {
        try 
        {
            $xml = parent::getFeedData();
            if ($xml === false) 
            {
                return false;
            }
        
            $server = Mage::getModel('csapiclient/server');
            $server->curl_opts[CURLOPT_TIMEOUT] = 3; // Don't hang login if server is down
            
            $endPoint = Mage::getStoreConfig('system/csnotification/api/notification_uri');

            // Get recommender version (this copies code in the Recommender module and should
            // be replaced in a module-agnostic way).
            $modules = (array)Mage::getConfig()->getNode('modules')->children();
            $recommenderVersion = "null";

            if(isset($modules['CommerceStack_Recommender']))
            {
                $module = $modules['CommerceStack_Recommender'];
                $recommenderVersion = (string)$module->version;
            }

            $endPoint .= '?recommender_version=' . $recommenderVersion;
            
            $commercestackXml = $server->get($endPoint, true);
            
            if(!$commercestackXml) return $xml;
            $commercestackXml = simplexml_load_string($commercestackXml);
            foreach($commercestackXml as $notification)
            {
                $item = $xml->channel->addChild('item');
                foreach($notification as $name => $value)
                {
                    $item->addChild($name, $value);
                }
            }
        }
        catch(Exception $e)
        {
            // Swallow and continue. Don't do anything fancy here and risk breaking the Admin login
        }
        
        return $xml;
    }
}