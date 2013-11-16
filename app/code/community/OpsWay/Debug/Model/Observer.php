<?php
/**
 * Base observer model
 *
 * @category Opsway
 * @package  Opsway_Debug
 * @author Alexandr Vronskiy <alvro@opsway.com>
 */
class OpsWay_Debug_Model_Observer
{
    /*
     * @var OpsWay_Debug_Helper_Data
     */
    protected $_helper;

    /*
     * @var bool
     */
    protected $_isEnabled = false;

    /**
     * @var float
     */
    protected $_timer;

    /**
     * @var array(
     */
    protected $_counter = array('models' => 0,'collections' => 0);

    public function __construct()
    {
        $this->_helper = Mage::helper('opsway_debug');
        $this->_isEnabled = $this->_helper->isEnabled();
        if ($this->_isEnabled){
            $this->_timer = gettimeofday(true);
            register_shutdown_function(array(&$this,'shutdownMagento'));
            $rId = $this->_helper->getRequestId();
            $headerLogMsg = "========================================\n";
            $headerLogMsg .= "BEGIN REQUEST ID: $rId (".date("Y-m-d H:i:s")."): \n";
            $headerLogMsg .= $this->_helper->getRequestVariableInfo() . "\n";
            $this->_helper->getWriter()->log($headerLogMsg,$rId);
        }
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function modelLoadAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled){
            return;
        }
        $object = $observer->getEvent()->getObject();
        $this->_counter['models']++;
        if ($object instanceof Varien_Object){
            $msg  = "LOAD MODEL (".get_class($object).") ID: {$object->getId()} \n";
            $msg .= "Current spent time: " . $this->_helper->getDiffTime($this->_timer) . " Current count: {$this->_counter['models']} \n";
            $msg .= "CALL STACK: " . $this->_helper->getLogBacktrace() . "\n";
            $this->_helper->getWriter()->log($msg,$this->_helper->getRequestId());
        }
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function collectionLoadAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled){
            return;
        }
        if ($observer->getEvent()->hasData('collection')){
            $collection = $observer->getEvent()->getCollection();
        } elseif ($observer->getEvent()->hasData('category_collection')){
            $collection = $observer->getEvent()->getCategoryCollection();
        } else {
            return;
        }
        $this->_counter['collections']++;
        /**
         * @var $collection Mage_Core_Model_Mysql4_Collection_Abstract
         */
        $sql = $collection->getSelectSql(true);
        $msg  = "LOAD COLLECTION (".get_class($collection)."), COUNT ITEMS: {$collection->count()} \n";
        $msg .= "Current spent time: " . $this->_helper->getDiffTime($this->_timer) . " Current count: {$this->_counter['collections']} \n";
        $msg .= "COLLECTION SQL: $sql \n";
        $msg .= "CALL STACK: " . $this->_helper->getLogBacktrace() . "\n";
        $this->_helper->getWriter()->log($msg,$this->_helper->getRequestId());
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     */
    public function endMagentoProcessing(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled){
            return;
        }
        $msg = "SUMMARIZE STATISTIC: \n" .
            "Collections load - {$this->_counter['models']};" . "Models load - {$this->_counter['collections']} \n";
        $this->_helper->getWriter()->log($msg,$this->_helper->getRequestId());
    }

    public function shutdownMagento()
    {
        if (!$this->_isEnabled){
            return;
        }
        $timeRequest = $this->_helper->getDiffTime($this->_timer);
        $rId = $this->_helper->getRequestId();
        $footerLogMsg = "END REQUEST ID: $rId. Run time: $timeRequest \n";
        $footerLogMsg .= "========================================\n\n\n";
        $this->_helper->getWriter()->log($footerLogMsg,$rId);

        if ( ! $err = error_get_last()) {
          return;
        }

        $fatals = array(
          E_USER_ERROR      => 'Fatal Error',
          E_ERROR           => 'Fatal Error',
          E_PARSE           => 'Parse Error',
          E_CORE_ERROR      => 'Core Error',
          E_CORE_WARNING    => 'Core Warning',
          E_COMPILE_ERROR   => 'Compile Error',
          E_COMPILE_WARNING => 'Compile Warning'
        );

        if (isset($fatals[$err['type']])) {
            $msg = $fatals[$err['type']] . ': ' . $err['message'] . ' in ';
            $msg.= $err['file'] . ' on line ' . $err['line'];
            error_log($msg);
            $this->_helper->getWriter()->log($msg,$rId);
        }

    }
}
