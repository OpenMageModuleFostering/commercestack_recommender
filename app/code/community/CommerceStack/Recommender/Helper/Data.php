<?php
class CommerceStack_Recommender_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_totalTasks;
    protected $_currentTask;
    protected $_totalFrames;
    protected $_currentFrame;

    protected function _authenticate($apiUser, $apiSecret)
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

    public function sync($apiUser, $apiSecret, $key, $value)
    {
        if(!$this->_authenticate($apiUser, $apiSecret))
        {
            echo "Invalid API user or key.";
            return;
        }

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

    // Driver for getTableUpdateAsXml when we are doing a client driven update
    public function postUpdate($apiUser, $apiSecret, $columns, $table, $primaryKey, $rootName, $chunkSize, $where = NULL)
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
            $tableUpdateXml = $this->getTableUpdateAsXml($apiUser, $apiSecret, $columns, $table, $primaryKey, $lastServerRecordId, $rootName, $chunkSize, $where);

            /** @var $server CommerceStack_CsApiClient_Model_Server */
            $server = $this->getServer();
            $server->post("{$rootName}/", /*. "?XDEBUG_SESSION_START=PHPSTORM",*/ $tableUpdateXml); // let exceptions bubble up

            if($this->_currentFrame != $this->_totalFrames)
            {
                $lastServerRecordId = $this->getFromServer($rootName);
            }

            $this->_currentFrame++;
        }
    }

    public function getTableUpdateAsXml($apiUser, $apiSecret, $columns, $table, $primaryKey, $lastRecordId, $rootName, $chunkSize, $where = NULL)
    {
        if(!$this->_authenticate($apiUser, $apiSecret))
        {
            echo "Invalid API user or key.";
            return;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $lastRecordId = (int)$lastRecordId;
        $sql = "SELECT $columns FROM $table WHERE $primaryKey > $lastRecordId";
        if(!is_null($where)) $sql .= " AND $where";
        $sql .= " ORDER BY $primaryKey ASC";
        if($chunkSize > 0) $sql .= " LIMIT $chunkSize";
        $result = $connection->fetchAll($sql);

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
        foreach ($result as $row)
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

    public function getMaxId($apiUser, $apiSecret, $table, $primaryKey, $where = null)
    {
        if(!$this->_authenticate($apiUser, $apiSecret))
        {
            echo "Invalid API user or key.";
            return;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT MAX($primaryKey) FROM $table";
        $result = $connection->query($sql);
        $maxId = $result->fetchColumn();

        return $maxId;
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
        $client->curl_opts[CURLOPT_TIMEOUT] = 3;
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
}  