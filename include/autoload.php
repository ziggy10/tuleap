<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloadcb66e8d5703512df8f9af454e97c921b($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'botmattermost_gitplugin' => '/botmattermost_gitPlugin.class.php',
            'tuleap\\botmattermostgit\\botmattermostgitnotification\\botmattermostgitnotification' => '/BotMattermostGit/BotMattermostGitNotification/BotMattermostGitNotification.php',
            'tuleap\\botmattermostgit\\botmattermostgitnotification\\dao' => '/BotMattermostGit/BotMattermostGitNotification/Dao.php',
            'tuleap\\botmattermostgit\\botmattermostgitnotification\\factory' => '/BotMattermostGit/BotMattermostGitNotification/Factory.php',
            'tuleap\\botmattermostgit\\botmattermostgitnotification\\validator' => '/BotMattermostGit/BotMattermostGitNotification/Validator.php',
            'tuleap\\botmattermostgit\\controller' => '/BotMattermostGit/Controller.php',
            'tuleap\\botmattermostgit\\exception\\cannotcreatebotnotificationexception' => '/BotMattermostGit/Exception/CannotCreateBotNotificationException.php',
            'tuleap\\botmattermostgit\\exception\\cannotdeletebotnotificationexception' => '/BotMattermostGit/Exception/CannotDeleteBotNotificationException.php',
            'tuleap\\botmattermostgit\\exception\\cannotupdatebotnotificationexception' => '/BotMattermostGit/Exception/CannotUpdateBotNotificationException.php',
            'tuleap\\botmattermostgit\\plugin\\plugindescriptor' => '/BotMattermostGit/Plugin/PluginDescriptor.php',
            'tuleap\\botmattermostgit\\plugin\\plugininfo' => '/BotMattermostGit/Plugin/PluginInfo.php',
            'tuleap\\botmattermostgit\\presenter' => '/BotMattermostGit/Presenter.php',
            'tuleap\\botmattermostgit\\senderservices\\gitnotificationbuilder' => '/BotMattermostGit/SenderServices/GitNotificationBuilder.php',
            'tuleap\\botmattermostgit\\senderservices\\gitnotificationsender' => '/BotMattermostGit/SenderServices/GitNotificationSender.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloadcb66e8d5703512df8f9af454e97c921b');
// @codeCoverageIgnoreEnd
