<?php
/**
 * Basic module helper
 *
 * @category Opsway
 * @package  Opsway_Debug
 * @author Alexandr Vronskiy <alvro@opsway.com>
 */
class OpsWay_Debug_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var OpsWay_Debug_Model_Writer_Abstract
     */
    protected $_writer;
    /**
     * @var string
     */
    protected $_requestId;

    public function __construct()
    {
        $writerName = $this->getSettingValue('writer');
        $this->_writer = Mage::getModel('opsway_debug/writer_'.$writerName);
        $this->_requestId = md5($this->_getRequest()->getOriginalRequest()->getRequestUri());
        if ($this->isEnabled() && $this->getSettingValue('profile_enabled','database')){
            $this->_enableDbProfiler();
        }
    }

    /**
     * @return OpsWay_Debug_Model_Writer_Abstract
     */
    public function getWriter(){
        return $this->_writer;
    }

    /**
     * @desc Return ID for current request URI
     * @return string
     */
    public function getRequestId(){
        return $this->_requestId;
    }

    public function getDiffTime($timer){
        return round((gettimeofday(true) - $timer) * 1000);
    }

    /**
     * @desc Get info about request
     * @return string
     */
    public function getRequestVariableInfo(){
        return  'ORGINAL_REQUEST_URI: ' . $this->_getRequest()->getOriginalRequest()->getRequestUri() . "\n" .
                'MAGENTO_REQUEST_URI: ' . $this->_getRequest()->getRequestUri() . "\n" .
                'MAGENTO MODULE: ' .  $this->_getRequest()->getModuleName() . "\n" .
                'MAGENTO CONTROLLER: ' .  $this->_getRequest()->getControllerName() . "\n" .
                'MAGENTO ACTION: ' .  $this->_getRequest()->getActionName() . "\n" .
                'METHOD: ' .  $this->_getRequest()->getMethod() . "\n" .
                'PARAMETERS: ' .  print_r($this->_getRequest()->getParams(),true) . "\n" .
                'COOKIE: ' . print_r($this->_getRequest()->getCookie(),true) . "\n";

    }

    /**
     * @desc Rewrite protected properties in Mysql Adapter to enable profiler
     * @return bool
     */
    protected function _enableDbProfiler(){

        try
        {
            foreach (explode(',',$this->getSettingValue('profile_connection','database')) as $conName){

                $connection = Mage::getSingleton('core/resource')->getConnection($conName);
                $rConnection = new ReflectionClass($connection);
                if ($rConnection->hasProperty('_debug')){
                    $debugProperty = $rConnection->getProperty('_debug');
                    $debugProperty->setAccessible(true);
                    $debugProperty->setValue($connection,true);
                }
                if ($rConnection->hasProperty('_logQueryTime')){
                    $logQueryTimeProperty = $rConnection->getProperty('_logQueryTime');
                    $logQueryTimeProperty->setAccessible(true);
                    $logQueryTimeProperty->setValue($connection,floatval($this->getSettingValue('profile_min_query_time','database')));
                }
                if ($rConnection->hasProperty('_logCallStack')){
                    $logCallStackProperty = $rConnection->getProperty('_logCallStack');
                    $logCallStackProperty->setAccessible(true);
                    $logCallStackProperty->setValue($connection,(bool)$this->getSettingValue('profile_callstack','database'));
                }
                $configWriter = $this->getWriter()->getConfig();
                switch ($this->getSettingValue('writer')){
                    case 'firephp':
                            //@todo Implement this in future, please.
                            if ($rConnection->hasProperty('_debugIoAdapter')){
                                $debugIoAdapterProperty = $rConnection->getProperty('_debugFile');
                                $debugIoAdapterProperty->setAccessible(true);
                                $debugIoAdapterProperty->setValue($connection,new StdClass('this is dummy for FirePHP'));
                            }
                        break;
                    case 'file':
                    default:
                    {
                        $debugFile = str_replace(Mage::getBaseDir() . DS, '', $configWriter['pathToDir']) . DS . $this->getRequestId() . '-SQL-' . $conName . '.log';
                        if ($rConnection->hasProperty('_debugFile')){
                            $debugFileProperty = $rConnection->getProperty('_debugFile');
                            $debugFileProperty->setAccessible(true);
                            $debugFileProperty->setValue($connection,$debugFile);
                        }
                    }
                }
            }

        } catch (ReflectionException $e) {
            Mage::logException($e);
            return false;
        }
        return true;
    }

    /*
     * @name Happy New Year!
     * @internal Not Yet Implemented
     */
    protected function _enableEval(){
        //Main idea: Developer can have backdoor for online debug?????????????????
        //Use hash secret (in confix.xml) for enable it in admin: compare password field from admin with secret hash
        //Use "evil" cookie with password for sending valid php code
        //Eval code run in debug observer functions with access (for echo?) to local variables (models, collections, etc)
    }

    /**
     * @desc  Getter for module config settings
     * @param string $name
     * @param string $section
     *
     * @return mixed
     */
    public function getSettingValue($name,$section = 'general'){
        return Mage::getStoreConfig("debug/$section/$name");
    }

    /**
     * @return bool
     */
    public function isEnabled(){
        return (bool)$this->getSettingValue('enabled');
    }

    /**
     * @return string
     */
    public function getLogBacktrace(){
        $output = '';
        $backtrace = debug_backtrace();
        foreach ($backtrace as $bt) {
            $args = '';
            foreach ($bt['args'] as $a) {
                if (!empty($args)) {
                    $args .= ', ';
                }
                switch (gettype($a)) {
                    case 'integer':
                    case 'double':
                        $args .= $a;
                        break;
                    case 'string':
                        $a = (substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
                        $args .= "\"$a\"";
                        break;
                    case 'array':
                        $args .= 'Array('.count($a).')';
                        break;
                    case 'object':
                        $args .= 'Object('.get_class($a).')';
                        break;
                    case 'resource':
                        $args .= 'Resource('.strstr($a, '#').')';
                        break;
                    case 'boolean':
                        $args .= $a ? 'True' : 'False';
                        break;
                    case 'NULL':
                        $args .= 'Null';
                        break;
                    default:
                        $args .= 'Unknown';
                }
            }
            //$output .= "\n";
            $output .= "file: {$bt['line']} - {$bt['file']}\n";
            $output .= "call: {$bt['class']}{$bt['type']}{$bt['function']}($args)\n";
        }

        return $output . "\n";
    }
}