<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloadeac75842318540e0c2c96bdb544f7960($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'proftpdplugin' => '/proftpdPlugin.class.php',
            'proftpdplugindescriptor' => '/ProftpdPluginDescriptor.class.php',
            'proftpdplugininfo' => '/ProftpdPluginInfo.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloadeac75842318540e0c2c96bdb544f7960');
// @codeCoverageIgnoreEnd