<?php
/**
 * Dropdown for multi select adapter for profiling
 *
 * @category Opsway
 * @package  Opsway_Debug
 * @author Alexandr Vronskiy <alvro@opsway.com>
 */
class OpsWay_Debug_Model_System_Config_Source_Connection
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'core_read',
                'label' => 'Read connection',
            ),
            array(
                'value' => 'core_write',
                'label' => 'Write connection',
            )
        );
    }
}