<?php
/**
 * Basic file writer
 *
 * @category Opsway
 * @package  Opsway_Debug
 * @author Alexandr Vronskiy <alvro@opsway.com>
 */
class OpsWay_Debug_Model_Writer_File extends OpsWay_Debug_Model_Writer_Abstract
{
    /**
     * @desc Folder for collect debug logs
     * @var string
     */
    protected $_debugDir;

    public function __construct(){
        $this->_debugDir = Mage::getBaseDir('log') . DS . 'debug';
        if (!is_dir($this->_debugDir)) {
            @mkdir($this->_debugDir, 0777);
            if (!file_exists($this->_debugDir)){
                throw new OpsWay_Debug_Model_Exception("Debug dir doesn't exists.");
            }
        }
    }

    public function getConfig()
    {
        return array(
            'pathToDir' => $this->_debugDir,
        );
    }

    public function log($msg,$requestId){
        @file_put_contents($this->_debugDir. DS . $requestId . '.log',$msg,FILE_APPEND); // $output;
    }
}