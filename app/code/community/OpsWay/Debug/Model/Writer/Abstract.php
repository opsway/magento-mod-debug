<?php
/**
 * Interface for writers
 *
 * @category Opsway
 * @package  Opsway_Debug
 * @author Alexandr Vronskiy <alvro@opsway.com>
 */
abstract class OpsWay_Debug_Model_Writer_Abstract
{
    abstract public function getConfig();
    abstract public function log($message,$requestId);
}