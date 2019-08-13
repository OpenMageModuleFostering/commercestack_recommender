<?php
class CommerceStack_Recommender_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_totalTasks;
    protected $_currentTask;
    protected $_totalFrames;
    protected $_currentFrame;
    private static $_columns = array(
        'orderitem' => 'item_id, order_id, parent_item_id, store_id, created_at, product_id, price',
        'logcustomer' => 'log_id, visitor_id, customer_id, store_id, login_at, logout_at',
        'logurlinfo' => 'url_id, url',
        'logurl' => 'url_id, visitor_id, visit_time',
        'urlrewrite' => 'url_rewrite_id AS id, store_id, target_path, request_path, product_id',
        'producturl' => 'v.value_id as id, v.entity_id as product_id, v.value as url_path',
        'catalogcategoryproductindex' => 'category_id, product_id'
    );

    public function isAuthenticated($apiUser, $apiSecret)
    {
        $account = Mage::getModel('csapiclient/account');
        return $account->authenticate($apiUser, $apiSecret);
    }

    public function getServer()
    {
        $server = Mage::getModel('csapiclient/server');
        $server->setClientModuleName('recommender');
        $server->setClientModuleVersion($this->_getRecommenderVersion());
        return $server;
    }

    public function sync($key, $value)
    {
        $config = new Mage_Core_Model_Config();
        $config->saveConfig("recommender/$key", $value);
    }

    public function getClientInfo()
    {
        return array (
            'commercestack_recommender_version' => $this->_getRecommenderVersion(),
            'mage_version' => Mage::getVersion(),
            'unsecure_base_url' => Mage::getStoreConfig('web/unsecure/base_url'),
            'secure_base_url' => Mage::getStoreConfig('web/secure/base_url'),
            'email' => Mage::getStoreConfig('recommender/account/email')
        );
    }

    public function requestUpdate($timeoutSecs = 10)
    {
        /** @var $server CommerceStack_CsApiClient_Model_Server */
        $server = $this->getServer();
        $clientInfo = $this->getClientInfo();
        $server->setTimeout($timeoutSecs);
        $server->post('update/' /*. "?XDEBUG_SESSION_START=PHPSTORM"*/, $clientInfo);
    }

    public function doClientDrivenUpdate()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(7200);
        $currentTask = 1;

        $this->setClientStatus('transferring_client_push');
        $this->setTotalTasks(8);

        $this->setIndexerStatus('STATUS_RUNNING');

        $this->setCurrentTask($currentTask);
        $this->postUpdate(
            'orderitem',
            $this->getTableNameSafe('sales/order_item'),
            'item_id',
            'orderitem',
            10000
        );

        $currentTask++;

        $this->setCurrentTask($currentTask);
        $this->postUpdate(
            'logcustomer',
            $this->getTableNameSafe('log/customer'),
            'log_id',
            'logcustomer',
            10000);

        $currentTask++;

        $this->setCurrentTask($currentTask);
        $this->postUpdate(
            'logurlinfo',
            $this->getTableNameSafe('log/url_info_table'),
            'url_id',
            'logurlinfo',
            10000);

        $currentTask++;

        $this->setCurrentTask($currentTask);
        $this->postUpdate(
            'logurl',
            $this->getTableNameSafe('log/url_table'),
            'url_id',
            'logurl',
            10000);

        $currentTask++;

        $this->setCurrentTask($currentTask);
        $this->prepareNewTransfer('urlrewrite'); // urlrewrite requires a full table update
        $this->postUpdate(
            'urlrewrite',
            $this->getTableNameSafe('core/url_rewrite'),
            'url_rewrite_id',
            'urlrewrite',
            10000, // must be 0. Server always returns 0 as last record id so chunking is not possible
            "product_id IS NOT null"
        );

        $currentTask++;

        $this->setCurrentTask($currentTask);
        $this->prepareNewTransfer('producturl'); // producturl requires a full table update
        $this->postUpdate(
            'producturl',
            (string)Mage::getConfig()->getTablePrefix() . 'catalog_product_entity_varchar v, ' . $this->getTableNameSafe('eav/attribute') . ' eav',
            'v.value_id',
            'producturl',
            10000,
            "v.attribute_id = eav.attribute_id AND eav.attribute_code='url_path'"
        );

        $currentTask++;

        $this->setCurrentTask($currentTask);
        $this->prepareNewTransfer('catalogcategoryproductindex'); // catalogcategoryproductindex requires a full table update
        $xml = $this->getTableAsXml(
            'catalogcategoryproductindex',
            $this->getTableNameSafe('catalog/category_product_index'),
            'catalogcategoryproductindex',
            'visibility > 1',
            'catalogcategoryproductindex');
        $this->postXml($xml, 'catalogcategoryproductindex');

        $currentTask++;

        $this->prepareNewRecs();

        $this->setCurrentTask($currentTask);
        /** @var CommerceStack_Recommender_Model_Product_Link $productLinks */
        $productLinks = Mage::getModel('recommender/product_link');

        try
        {
            $productLinks->update();
        }
        catch(CsApiClient_Server_Forbidden $e)
        {
            // Requires subscription
            $rpmConfigUrl = Mage::getSingleton('adminhtml/url')->getUrl('*/system_config/edit/section/recommender');
            Mage::getSingleton('core/session')->addError('Your free trial of Related Products Manager has expired. Please <a href="'
                . $rpmConfigUrl . '#recommender_account-head" onclick=openAccountTab();><strong>subscribe now</strong></a> to complete reindexing.');
            $this->setClientStatus('complete');
            throw $e;
        }

        $this->setClientStatus('complete');
        $this->setIndexerStatus('STATUS_PENDING');
    }


    public function postXml($xml, $rootName)
    {
        /** @var $server CommerceStack_CsApiClient_Model_Server */
        $server = $this->getServer();
        $server->post("{$rootName}/" /*. "?XDEBUG_SESSION_START=PHPSTORM"*/, $xml); // let exceptions bubble up
    }

    public function postUpdate($columnKey, $table, $primaryKey, $rootName, $chunkSize, $where = NULL)
    {
        // Get last server record ID from server
        $lastServerRecordId = (int)$this->getFromServer($rootName); // If this fails there's no point in continuing. Let exception bubble up

        // Get count of records we'll need to send
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT COUNT($primaryKey) FROM $table WHERE $primaryKey > $lastServerRecordId";
        if(!is_null($where)) $sql .= " AND $where";
        $result = $connection->query($sql);
        $totalRecordCount = $result->fetchColumn();
        if($totalRecordCount <= 0) return;

        $chunkSize == 0 ? $this->_totalFrames = 1 : $this->_totalFrames = ceil($totalRecordCount/$chunkSize);

        $this->_currentFrame = 1;
        while($this->_currentFrame <= $this->_totalFrames)
        {
            $tableUpdateXml = $this->getTableUpdateAsXml($columnKey, $table, $primaryKey, $lastServerRecordId, $rootName, $chunkSize, $where);

            $this->postXml($tableUpdateXml, $rootName);

            if($this->_currentFrame != $this->_totalFrames)
            {
                $lastServerRecordId = $this->getFromServer($rootName);
            }

            $this->_currentFrame++;
        }
    }

    public function getTableAsXml($columnKey, $table, $rootName, $where = null, $groupByKey = null)
    {
        // This does not support chunking
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT " . self::$_columns[$columnKey] . " FROM $table";
        if(!is_null($where)) $sql .= " WHERE $where";
        if(!is_null($groupByKey)) $sql .= " GROUP BY " . self::$_columns[$groupByKey];
        $result = $connection->fetchAll($sql);
        return $this->recordsetToXml($rootName, $result);
    }

    public function getTableUpdateAsXml($columnKey, $table, $primaryKey, $lastRecordId, $rootName, $chunkSize, $where = NULL)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $lastRecordId = (int)$lastRecordId;
        $sql = "SELECT " . self::$_columns[$columnKey] . " FROM $table WHERE $primaryKey > $lastRecordId";
        if(!is_null($where)) $sql .= " AND $where";
        $sql .= " ORDER BY $primaryKey ASC";
        if($chunkSize > 0) $sql .= " LIMIT $chunkSize";
        $result = $connection->fetchAll($sql);

        return $this->recordsetToXml($rootName, $result);
    }

    public function recordsetToXml($rootName, $recordset)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= "<{$rootName}s>\n";
        $xml .= "<commercestack_recommender_version>{$this->_getRecommenderVersion()}</commercestack_recommender_version>\n";

        // If this is a client driven update we need to update the server of progress
        if(!is_null($this->_currentTask))
            $xml .= "<current_task>{$this->_currentTask}</current_task>\n";
        if(!is_null($this->_totalTasks))
            $xml .= "<total_tasks>{$this->_totalTasks}</total_tasks>\n";
        if(!is_null($this->_currentFrame))
            $xml .= "<current_frame>{$this->_currentFrame}</current_frame>\n";
        if(!is_null($this->_totalFrames))
            $xml .= "<total_frames>{$this->_totalFrames}</total_frames>\n";
        // END server progress update for client driven update

        // We cannot rely on XMLWriter being available so we construct the XML manually
        foreach ($recordset as $row)
        {
            $xml .= "	<$rootName>\n";
            foreach($row as $key => $value)
            {
                // Strip $value of any CDATA delimiters
                $value = str_replace("]]>", "", $value);

                $xml .= "		<" . $key . "><![CDATA[" . $value . "]]></" . $key . ">\n";
            }
            $xml .= "	</$rootName>\n";
        }

        $xml .= "</{$rootName}s>\n";
        return $xml;
    }

    public function getMaxId($table, $primaryKey, $where = null)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT MAX($primaryKey) FROM $table";
        $result = $connection->query($sql);
        $maxId = $result->fetchColumn();

        return $maxId;
    }

    public function getCount($table, $where = null, $groupByKey = null)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        if(is_null($groupByKey))
        {
            $sql = "SELECT COUNT(*) FROM $table";
            if(!is_null($where)) $sql .= " WHERE $where";
        }
        else
        {
            $fromClause = "SELECT " . self::$_columns[$groupByKey] . " FROM $table";
            if(!is_null($where)) $fromClause .= " WHERE $where";
            $fromClause .= " GROUP BY " . self::$_columns[$groupByKey];
            $sql = "SELECT COUNT(*) FROM ($fromClause) AS cnt";
        }

        $result = $connection->query($sql);
        $count = $result->fetchColumn();

        return $count;
    }

    public function getFromServer($rootName)
    {
        $server = $this->getServer();
        
        try 
        {
            $xml = $server->get("{$rootName}/");
            //$xml = $server->get("$uri&start_debug=1&debug_host=127.0.0.1&debug_port=10137&original_url=http%3A%2F%2Flocalhost%2Frecommender%2Fpublic%2Forder&use_remote=1");
            return $xml;
        }
        catch(CsApiClient_Server_Forbidden $e)
        {
            throw $e;
        }
        catch(Exception $e)
        {
            $this->reportException($e);
        }
    }
    
    protected function _getRecommenderVersion()
    {
		$modules = (array)Mage::getConfig()->getNode('modules')->children();
		$module = $modules['CommerceStack_Recommender'];
		return (string)$module->version;
    }

    public function reportException($e)
    {
        $server = $this->getServer();
        $server->curl_opts[CURLOPT_TIMEOUT] = 3;
        $errorReport = $e->getMessage() . "\n" . $e->getTraceAsString();
        
        try 
        {
            $server->post("exception/", $errorReport);
        }
        catch(Exception $e)
        {
            //throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getTableNameSafe($modelEntity)
    {
        try 
        {
            $tableName = Mage::getSingleton('core/resource')->getTableName($modelEntity);
        }
        catch(Exception $e)
        {
            $this->reportException($e);
        }
        
        return $tableName;
    }

    public function setTotalTasks($totalTasks)
    {
        $this->_totalTasks = $totalTasks;
    }

    public function setCurrentTask($curTask)
    {
        $this->_currentTask = $curTask;
    }

    public function setClientStatus($clientStatus)
    {
        $server = $this->getServer();

        try
        {
            $server->post('status', $clientStatus);
        }
        catch(Exception $e)
        {
            $this->reportException($e);
        }
    }

    public function prepareNewTransfer($rootName)
    {
        $server = $this->getServer();

        try
        {
            $server->post($rootName, 'BeginTransfer');
        }
        catch(Exception $e)
        {
            $this->reportException($e);
        }
    }

    public function prepareNewRecs()
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connection->query("TRUNCATE TABLE " . $this->getTableNameSafe('recommender_product_link'));
    }

    public function setIndexerStatus($status)
    {
        $indexer = Mage::getModel('recommender/product_indexer_category');
        $indexer->setStatus($status);
    }
}  