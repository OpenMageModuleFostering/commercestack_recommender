<?php
class CommerceStack_Recommender_IndexController extends Mage_Core_Controller_Front_Action
{
    private static $_authFailureMsg = "Invalid API key";


    public function requestUpdateAction()
    {
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        $dataHelper->requestUpdate();
    }

    public function orderitemMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $maxId = $dataHelper->getMaxId($dataHelper->getTableNameSafe('sales/order_item'), 'item_id');

        echo $maxId;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function orderitemAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = $dataHelper->getTableUpdateAsXml(
            'orderitem',
            $dataHelper->getTableNameSafe('sales/order_item'),
            'item_id',
            $this->getRequest()->getParam('max_server_id'),
            'orderitem',
            $this->getRequest()->getParam('chunk_size')
        );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function logcustomerMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $maxId = $dataHelper->getMaxId(
            $dataHelper->getTableNameSafe('log/customer'),
            'log_id');

        echo $maxId;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function logcustomerAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = $dataHelper->getTableUpdateAsXml(
            'logcustomer',
            $dataHelper->getTableNameSafe('log/customer'),
            'log_id',
            $this->getRequest()->getParam('max_server_id'),
            'logcustomer',
            $this->getRequest()->getParam('chunk_size')
        );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function logurlinfoMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $maxId = $dataHelper->getMaxId(
            $dataHelper->getTableNameSafe('log/url_info_table'),
            'url_id');

        echo $maxId;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function logurlinfoAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = $dataHelper->getTableUpdateAsXml(
            'logurlinfo',
            $dataHelper->getTableNameSafe('log/url_info_table'),
            'url_id',
            $this->getRequest()->getParam('max_server_id'),
            'logurlinfo',
            $this->getRequest()->getParam('chunk_size')
        );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function logurlMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $maxId = $dataHelper->getMaxId($dataHelper->getTableNameSafe('log/url_table'), 'url_id');

        echo $maxId;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function logurlAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = $dataHelper->getTableUpdateAsXml(
            'logurl',
            $dataHelper->getTableNameSafe('log/url_table'),
            'url_id',
            $this->getRequest()->getParam('max_server_id'),
            'logurl',
            $this->getRequest()->getParam('chunk_size')
        );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function urlrewriteMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $maxId = $dataHelper->getMaxId(
           $dataHelper->getTableNameSafe('core/url_rewrite') . " WHERE product_id IS NOT null",
            'url_rewrite_id');

        echo $maxId;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function urlrewriteAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        // "url_rewrite_id AS id" required for backward compatibility
        $xml = $dataHelper->getTableUpdateAsXml(
            'urlrewrite',
            $dataHelper->getTableNameSafe('core/url_rewrite'),
            'url_rewrite_id',
            $this->getRequest()->getParam('max_server_id'),
            'urlrewrite',
            $this->getRequest()->getParam('chunk_size'),
            "product_id IS NOT null"
        );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function producturlMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $maxId = $dataHelper->getMaxId(
            (string)Mage::getConfig()->getTablePrefix() . 'catalog_product_entity_varchar v, ' . $dataHelper->getTableNameSafe('eav/attribute') . " eav WHERE v.attribute_id = eav.attribute_id AND eav.attribute_code='url_path'",
            'value_id');

        echo $maxId;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function producturlAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        // "v.value_id AS id" required for backward compatibility
        $xml = $dataHelper->getTableUpdateAsXml(
            'producturl',
            (string)Mage::getConfig()->getTablePrefix() . 'catalog_product_entity_varchar v, ' . $dataHelper->getTableNameSafe('eav/attribute') . ' eav',
            'v.value_id',
            $this->getRequest()->getParam('max_server_id'),
            'producturl',
            $this->getRequest()->getParam('chunk_size'),
            "v.attribute_id = eav.attribute_id AND eav.attribute_code='url_path'"
        );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function catalogcategoryproductindexMaxIdAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $count = $dataHelper->getCount(
            $dataHelper->getTableNameSafe('catalog/category_product_index'),
            'visibility > 1',
            'catalogcategoryproductindex'
        );

        echo $count;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function catalogcategoryproductindexAction()
    {
        set_time_limit(1800);

        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = $dataHelper->getTableAsXml(
            'catalogcategoryproductindex',
            $dataHelper->getTableNameSafe('catalog/category_product_index'),
            'catalogcategoryproductindex',
            'visibility > 1',
            'catalogcategoryproductindex'
            );

        echo $xml;

        exit(); // Suppress any cache displays or other extension processing
    }

    public function syncAction()
    {
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $dataHelper->sync($this->getRequest()->getParam('key'),
            $this->getRequest()->getParam('value'));
    }

    public function preparenewrecsAction()
    {
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $dataHelper->prepareNewRecs();
    }

    public function marketbasketAction()
    {
        set_time_limit(1800);
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = simplexml_load_string($this->getRequest()->getParam('recs'));

        /** @var CommerceStack_Recommender_Model_Product_Link $productLinks */
        $productLinks = Mage::getModel('recommender/product_link');
        $productLinks->updateFromXml($xml, 5, 'marketbasket'); // Mage_Catalog_Model_Product_Link::LINK_TYPE_CROSSSELL. PHP 5.2.x doesn't allow accessing class const
    }

    public function alsoviewedAction()
    {
        set_time_limit(1800);
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = simplexml_load_string($this->getRequest()->getParam('recs'));
        /** @var CommerceStack_Recommender_Model_Product_Link $productLinks */
        $productLinks = Mage::getModel('recommender/product_link');
        $productLinks->updateFromXml($xml, 1, 'alsoviewed'); // Mage_Catalog_Model_Product_Link::LINK_TYPE_RELATED. PHP 5.2.x doesn't allow accessing class const
    }

    public function rulesbasedrelatedAction()
    {
        set_time_limit(1800);
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = simplexml_load_string($this->getRequest()->getParam('recs'));
        /** @var CommerceStack_Recommender_Model_Product_Link $productLinks */
        $productLinks = Mage::getModel('recommender/product_link');
        $productLinks->updateFromXml($xml, 1, 'rulesbasedrelated'); // Mage_Catalog_Model_Product_Link::LINK_TYPE_RELATED. PHP 5.2.x doesn't allow accessing class const
    }

    public function rulesbasedcrosssellAction()
    {
        set_time_limit(1800);
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $xml = simplexml_load_string($this->getRequest()->getParam('recs'));
        /** @var CommerceStack_Recommender_Model_Product_Link $productLinks */
        $productLinks = Mage::getModel('recommender/product_link');
        $productLinks->updateFromXml($xml, 5, 'rulesbasedcrosssell'); // Mage_Catalog_Model_Product_Link::LINK_TYPE_RELATED. PHP 5.2.x doesn't allow accessing class const
    }

    public function updateAction()
    {
        try
        {
            /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
            $dataHelper = Mage::helper('recommender');
            if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
                $this->getRequest()->getParam('api_secret')))
            {
                echo self::$_authFailureMsg;
                return;
            }

            /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
            $dataHelper = Mage::helper('recommender');
            session_write_close(); // prevent other requests from blocking during update because of locked session file
            $dataHelper->doClientDrivenUpdate();
            $this->getResponse()->setBody("");
        }
        catch(CommerceStack_Search_Helper_Pest_Forbidden $e)
        {
            // A subscription plan is required (this will be handled by Pest so
            // should never occur here
            session_write_close();
        }
        catch(Exception $e)
        {
            $dataHelper->reportException($e);
            session_write_close();
        }
    }

    public function setindexerstatusAction()
    {
        /** @var $dataHelper CommerceStack_Recommender_Helper_Data */
        $dataHelper = Mage::helper('recommender');
        if(!$dataHelper->isAuthenticated($this->getRequest()->getParam('api_user'),
            $this->getRequest()->getParam('api_secret')))
        {
            echo self::$_authFailureMsg;
            return;
        }

        $status = $this->getRequest()->getParam('indexer_status');
        $dataHelper->setIndexerStatus($status);
    }
}