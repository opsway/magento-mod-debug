<?php
/**
 * Dropdown for select writer
 *
 * @category Opsway
 * @package  Opsway_Debug
 * @author Alexandr Vronskiy <alvro@opsway.com>
 */
class OpsWay_Debug_Model_System_Config_Source_Writer
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'file',
                'label' => 'Local Files',
            ),
        );
    }
}