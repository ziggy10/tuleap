<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Dashboard\User\AtUserCreationDefaultWidgetsCreator;
use Tuleap\Glyph\GlyphLocation;
use Tuleap\Glyph\GlyphLocationsCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\TemplatePresenter;
use Tuleap\project\Event\ProjectRegistrationActivateService;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Project\XML\Export\NoArchive;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CurrentPage;
use Tuleap\Service\ServiceCreator;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDuplicator;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletor;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionDAO;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionRemover;
use Tuleap\Tracker\Artifact\Changeset\Notification\AsynchronousSupervisor;
use Tuleap\Tracker\Artifact\Changeset\Notification\NotifierDao;
use Tuleap\Tracker\Artifact\Changeset\Notification\RecipientsManager;
use Tuleap\Tracker\Artifact\LatestHeartbeatsCollector;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\BurndownCalculator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\FieldCalculator;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_DAILY;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_GENERATE;
use Tuleap\Tracker\Import\Spotter;
use Tuleap\Tracker\Legacy\Inheritor;
use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserInvolvedInNotificationPresenterBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationSubscribersFilter;
use Tuleap\Tracker\Notifications\NotificationLevelExtractor;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\NotificationsForceUsageUpdater;
use Tuleap\Tracker\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\Tracker\Notifications\Settings\NotificationsAdminSettingsDisplayController;
use Tuleap\Tracker\Notifications\Settings\NotificationsAdminSettingsUpdateController;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsDAO;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Notifications\TrackerForceNotificationsLevelCommand;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UgroupsToNotifyUpdater;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\Tracker\PermissionsPerGroup\ProjectAdminPermissionPerGroupPresenterBuilder;
use Tuleap\Tracker\ProjectDeletionEvent;
use Tuleap\Tracker\Reference\ReferenceCreator;
use Tuleap\Tracker\Service\ServiceActivator;
use Tuleap\Tracker\Webhook\Actions\WebhookCreateController;
use Tuleap\Tracker\Webhook\Actions\WebhookDeleteController;
use Tuleap\Tracker\Webhook\Actions\WebhookEditController;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookRetriever;
use Tuleap\User\History\HistoryRetriever;
use Tuleap\Widget\Event\GetPublicAreas;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/manual_autoload.php';

/**
 * trackerPlugin
 */
class trackerPlugin extends Plugin {

    const EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE      = 'forge__artifacts';
    const EMAILGATEWAY_INSECURE_ARTIFACT_CREATION = 'forge__tracker';
    const EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE   = 'forge__artifact';
    const SERVICE_SHORTNAME                       = 'plugin_tracker';
    const TRUNCATED_SERVICE_NAME                  = 'Trackers';

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-tracker', __DIR__.'/../site-content');

        $this->addHook('javascript_file');
        $this->addHook('cssfile',                             'cssFile',                           false);
        $this->addHook(Event::GET_AVAILABLE_REFERENCE_NATURE, 'get_available_reference_natures',   false);
        $this->addHook(Event::GET_ARTIFACT_REFERENCE_GROUP_ID,'get_artifact_reference_group_id',   false);
        $this->addHook(Event::SET_ARTIFACT_REFERENCE_GROUP_ID);
        $this->addHook(Event::BUILD_REFERENCE,                'build_reference',                   false);
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipContentEvent::NAME);
        $this->addHook(Event::SERVICE_CLASSNAMES,             'service_classnames',                false);
        $this->addHook(Event::JAVASCRIPT,                     'javascript',                        false);
        $this->addHook(Event::TOGGLE,                         'toggle',                            false);
        $this->addHook(GetPublicAreas::NAME);
        $this->addHook('permission_get_name',                 'permission_get_name',               false);
        $this->addHook('permission_get_object_type',          'permission_get_object_type',        false);
        $this->addHook('permission_get_object_name',          'permission_get_object_name',        false);
        $this->addHook('permission_get_object_fullname',      'permission_get_object_fullname',    false);
        $this->addHook('permission_user_allowed_to_change',   'permission_user_allowed_to_change', false);
        $this->addHook(Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS,         'getSystemEventClass',               false);

        $this->addHook('url_verification_instance',           'url_verification_instance',         false);

        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
        $this->addHook(AtUserCreationDefaultWidgetsCreator::DEFAULT_WIDGETS_FOR_NEW_USER);

        $this->addHook('project_is_deleted',                  'project_is_deleted',                false);
        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook('codendi_daily_start',                 'codendi_daily_start',               false);
        $this->addHook('fill_project_history_sub_events',     'fillProjectHistorySubEvents',       false);
        $this->addHook(Event::SOAP_DESCRIPTION,               'soap_description',                  false);
        $this->addHook(Event::IMPORT_XML_PROJECT);
        $this->addHook(Event::IMPORT_XML_IS_PROJECT_VALID);
        $this->addHook(Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT);
        $this->addHook(Event::USER_MANAGER_GET_USER_INSTANCE);
        $this->addHook('plugin_statistics_service_usage');
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_GET_PROJECT_TRACKERS);
        $this->addHook(Event::REST_OPTIONS_PROJECT_TRACKERS);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(Event::BACKEND_ALIAS_GET_ALIASES);
        $this->addHook(Event::GET_PROJECTID_FROM_URL);
        $this->addHook(Event::SITE_ADMIN_CONFIGURATION_TRACKER);
        $this->addHook(Event::EXPORT_XML_PROJECT);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::CAN_USER_ACCESS_UGROUP_INFO);
        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);
        $this->addHook('site_admin_option_hook');
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);

        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook('project_admin_remove_user');
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook(Event::USER_HISTORY, 'getRecentlyVisitedArtifacts');
        $this->addHook(Event::USER_HISTORY_CLEAR, 'clearRecentlyVisitedArtifacts');

        $this->addHook(ProjectCreator::PROJECT_CREATION_REMOVE_LEGACY_SERVICES);
        $this->addHook(ProjectRegistrationActivateService::NAME);

        $this->addHook(WorkerEvent::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);

        $this->addHook(\Tuleap\user\UserAutocompletePostSearchEvent::NAME);

        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);

        $this->addHook(CLICommandsCollector::NAME);
    }

    public function getHooksAndCallbacks() {
        if (defined('AGILEDASHBOARD_BASE_DIR')) {
            $this->addHook(AGILEDASHBOARD_EXPORT_XML);

            // REST Milestones
            $this->addHook(AGILEDASHBOARD_EVENT_REST_GET_MILESTONE);
            $this->addHook(AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN);
            $this->addHook(AGILEDASHBOARD_EVENT_REST_OPTIONS_BURNDOWN);
        }
        if (defined('STATISTICS_BASE_DIR')) {
            $this->addHook(Statistics_Event::FREQUENCE_STAT_ENTRIES);
            $this->addHook(Statistics_Event::FREQUENCE_STAT_SAMPLE);
        }
        if (defined('FULLTEXTSEARCH_BASE_URL')) {
            $this->addHook(FULLTEXTSEARCH_EVENT_FETCH_ALL_DOCUMENT_SEARCH_TYPES);
            $this->addHook(FULLTEXTSEARCH_EVENT_FETCH_PROJECT_TRACKER_FIELDS);
            $this->addHook(FULLTEXTSEARCH_EVENT_DOES_TRACKER_SERVICE_USE_UGROUP);
        }

        $this->addHook(Event::LIST_DELETED_TRACKERS);
        $this->addHook(TemplatePresenter::EVENT_ADDITIONAL_ADMIN_BUTTONS);

        $this->addHook(GlyphLocationsCollector::NAME);
        $this->addHook(HeartbeatsEntryCollection::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'trackerPluginInfo')) {
            include_once('trackerPluginInfo.class.php');
            $this->pluginInfo = new trackerPluginInfo($this);
        }
        return $this->pluginInfo;
    }


    /**
     * @see Event::PROCCESS_SYSTEM_CHECK
     */
    public function proccess_system_check(array $params) {
        $file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            $this->getUserManager(),
            new Tracker_Artifact_Attachment_TemporaryFileManagerDao(),
            new Tracker_FileInfoFactory(
                new Tracker_FileInfoDao(),
                Tracker_FormElementFactory::instance(),
                Tracker_ArtifactFactory::instance()
            ),
            new System_Command(),
            ForgeConfig::get('sys_file_deletion_delay')
        );

        $file_manager->purgeOldTemporaryFiles();

        $this->getAsynchronousSupervisor($params['logger'])->runSystemCheck();
    }

    private function getAsynchronousSupervisor(Logger $logger)
    {
        return new AsynchronousSupervisor(
            $logger,
            new NotifierDao()
        );
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_ENTRIES
     */
    public function plugin_statistics_frequence_stat_entries($params) {
        $params['entries'][$this->getServiceShortname()] = 'Opened artifacts';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_SAMPLE
     */
    public function plugin_statistics_frequence_stat_sample($params) {
        if ($params['character'] === $this->getServiceShortname()) {
            $params['sample'] = new Tracker_Sample();
        }
    }

    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => $GLOBALS['Language']->getText('plugin_tracker', 'descriptor_name'),
            'href'  => $this->getPluginPath() . '/config.php'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath().'/config.php') === 0 ||
            $this->isInDashboard() ||
            $this->isInTrackerGlobalAdmin()
        ) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function cssFile() {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(TRACKER_EVENT_INCLUDE_CSS_FILE, array('include_tracker_css_file' => &$include_tracker_css_file));
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if ($include_tracker_css_file ||
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            $include_assets = new IncludeAssets(
                TRACKER_BASE_DIR . '/../www/themes/FlamingParrot/assets',
                TRACKER_BASE_URL . '/themes/FlamingParrot/assets'
            );

            $style_css_url = $include_assets->getFileURL('style.css');
            $print_css_url = $include_assets->getFileURL('print.css');

            echo '<link rel="stylesheet" type="text/css" href="'.$style_css_url.'" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$print_css_url.'" media="print" />';
            if (file_exists($this->getThemePath().'/css/ieStyle.css')) {
                $ie_style_css_url = $include_assets->getFileURL('ieStyle.css');
                echo '<!--[if lte IE 8]><link rel="stylesheet" type="text/css" href="'.$ie_style_css_url.'" /><![endif]-->';
            }
        }
    }

    public function burning_parrot_get_stylesheets($params)
    {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(TRACKER_EVENT_INCLUDE_CSS_FILE, array('include_tracker_css_file' => &$include_tracker_css_file));

        if ($include_tracker_css_file ||
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            $this->isInDashboard() ||
            $this->isInTrackerGlobalAdmin()
        ) {
            $theme_include_assets    = new IncludeAssets(
                __DIR__ . '/../www/themes/BurningParrot/assets',
                $this->getThemePath() . '/assets'
            );
            $variant                 = $params['variant'];
            $params['stylesheets'][] = $theme_include_assets->getFileURL('style-' . $variant->getName() . '.css');
        }
    }

    public function javascript_file($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/config.php') === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/admin-nature.js"></script>'.PHP_EOL;
        }
        if ($this->currentRequestIsForPlugin() || $this->currentRequestIsForDashboards()) {
            echo $this->getMinifiedAssetHTML().PHP_EOL;
        }
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/config.php') === 0) {
            $params['javascript_files'][] = $this->getPluginPath() .'/scripts/admin-nature.js';
            $params['javascript_files'][] = '/scripts/tuleap/manage-allowed-projects-on-resource.js';
        }

        if ($this->isInTrackerGlobalAdmin()) {
            $params['javascript_files'][] = $this->getPluginPath() .'/scripts/global-admin.js';
        }

        if ($this->isInPermissionsPerGroupProjectAdmin()) {
            $include_assets = new IncludeAssets(
                TRACKER_BASE_DIR . '/../www/assets',
                $this->getPluginPath() . '/assets'
            );

            $GLOBALS['HTML']->includeFooterJavascriptFile(
                $include_assets->getFileURL('tracker-permissions-per-group.js')
            );
        }
    }

    private function isInPermissionsPerGroupProjectAdmin()
    {
        return strpos($_SERVER['REQUEST_URI'], '/project/admin/permission_per_group') === 0;
    }

    /**
     *This callback make SystemEvent manager knows about Tracker plugin System Events
     */
    public function getSystemEventClass($params) {
        switch($params['type']) {
            case SystemEvent_TRACKER_V3_MIGRATION::NAME:
                $params['class']        = 'SystemEvent_TRACKER_V3_MIGRATION';
                $params['dependencies'] = array(
                    $this->getMigrationManager(),
                );
                break;
            case 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME:
                $params['class']        = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME;
                $params['dependencies'] = array(
                    new Tracker_FormElement_Field_BurndownDao(),
                    new FieldCalculator(new BurndownCalculator(new Tracker_FormElement_Field_ComputedDao())),
                    new Tracker_FormElement_Field_ComputedDaoCache(new Tracker_FormElement_Field_ComputedDao()),
                    new BackendLogger(),
                    new BurndownCacheDateRetriever()
                );
                break;
            case 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME:
                $params['class']        = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME;
                $params['dependencies'] = array(
                    new Tracker_FormElement_Field_BurndownDao(),
                    new FieldCalculator(new BurndownCalculator(new Tracker_FormElement_Field_ComputedDao())),
                    new Tracker_FormElement_Field_ComputedDaoCache(new Tracker_FormElement_Field_ComputedDao()),
                    new BackendLogger(),
                    new BurndownCacheDateRetriever()
                );
                break;
            default:
                break;
        }
    }

    public function service_classnames($params) {
        include_once 'ServiceTracker.class.php';
        $params['classnames'][$this->getServiceShortname()] = 'ServiceTracker';
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function javascript($params) {
        // TODO: Move this in ServiceTracker::displayHeader()
        include $GLOBALS['Language']->getContent('script_locale', null, 'tracker');
        echo PHP_EOL;
        echo "codendi.tracker = codendi.tracker || { };".PHP_EOL;
        echo "codendi.tracker.base_url = '". TRACKER_BASE_URL ."/';".PHP_EOL;
    }

    public function toggle($params) {
        if ($params['id'] === 'tracker_report_query_0') {
            Toggler::togglePreference($params['user'], $params['id']);
            $params['done'] = true;
        } else if (strpos($params['id'], 'tracker_report_query_') === 0) {
            $report_id = (int)substr($params['id'], strlen('tracker_report_query_'));
            $report_factory = Tracker_ReportFactory::instance();
            if (($report = $report_factory->getReportById($report_id, $params['user']->getid())) && $report->userCanUpdate($params['user'])) {
                $report->toggleQueryDisplay();
                $report_factory->save($report);
            }
            $params['done'] = true;
        }
    }

    private function isLegacyTrackerV3StillUsed($legacy)
    {
        return $legacy[Service::TRACKERV3];
    }

   /**
    * Project creation hook
    *
    * @param Array $params
    */
    public function register_project_creation($params) {
        if ($params['project_creation_data']->projectShouldInheritFromTemplate()) {
            $tracker_manager = new TrackerManager();
            $tracker_manager->duplicate($params['template_id'], $params['group_id'], $params['ugroupsMapping']);

            $project_manager = $this->getProjectManager();
            $template        = $project_manager->getProject($params['template_id']);
            $project         = $project_manager->getProject($params['group_id']);
            $legacy_services = $params['legacy_service_usage'];

            if (
                ! $this->isRestricted() &&
                ! $this->isLegacyTrackerV3StillUsed($legacy_services)
                && TrackerV3::instance()->available()
            ) {
                $inheritor = new Inheritor(
                    new ArtifactTypeFactory($template),
                    $this->getTrackerFactory()
                );

                $inheritor->inheritFromLegacy($this->getUserManager()->getCurrentUser(), $template, $project);
            }

            $artifact_link_types_duplicator = new ArtifactLinksUsageDuplicator(new ArtifactLinksUsageDao());
            $artifact_link_types_duplicator->duplicate($template, $project);
        }
    }

    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_submit');
                break;
            case 'PLUGIN_TRACKER_FIELD_READ':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_read');
                break;
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_update');
                break;
            case Tracker::PERMISSION_SUBMITTER_ONLY:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_submitter_only_access');
                break;
            case Tracker::PERMISSION_SUBMITTER:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_submitter_access');
                break;
            case Tracker::PERMISSION_ASSIGNEE:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_assignee_access');
                break;
            case Tracker::PERMISSION_FULL:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_full_access');
                break;
            case Tracker::PERMISSION_ADMIN:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_admin');
                break;
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_artifact_access');
                break;
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                $params['name'] = $GLOBALS['Language']->getText('workflow_admin','permissions_transition');
                break;
            default:
                break;
            }
        }
    }

    function permission_get_object_type($params) {
        $type = $this->getObjectTypeFromPermissions($params);
        if ($type != false) {
            $params['object_type'] = $type;
        }
    }

    function getObjectTypeFromPermissions($params) {
        switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
            case 'PLUGIN_TRACKER_FIELD_READ':
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                return 'field';
            case Tracker::PERMISSION_SUBMITTER_ONLY:
            case Tracker::PERMISSION_SUBMITTER:
            case Tracker::PERMISSION_ASSIGNEE:
            case Tracker::PERMISSION_FULL:
            case Tracker::PERMISSION_ADMIN:
                return 'tracker';
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                return 'artifact';
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                return 'workflow transition';
        }
        return false;
    }

    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            $type = $this->getObjectTypeFromPermissions($params);
            if (in_array($params['permission_type'], array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER_ONLY, 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
                $object_id = $params['object_id'];
                if ($type == 'tracker') {
                    $ret = (string)$object_id;
                    if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                        $params['object_name'] = $tracker->getName();
                    }
                } else if ($type == 'field') {
                    $ret = (string)$object_id;
                    if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                        $ret     = $field->getLabel();
                        $tracker = $field->getTracker();
                        if ($tracker !== null) {
                            $ret .= ' ('. $tracker->getName() .')';
                        }
                    }
                    $params['object_name'] =  $ret;
                } else if ($type == 'artifact') {
                    $ret = (string)$object_id;
                    if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                        $ret = 'art #'. $a->getId();
                        $semantics = $a->getTracker()
                                       ->getTrackerSemanticManager()
                                       ->getSemantics();
                        if (isset($semantics['title'])) {
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($semantics['title']->getFieldId())) {
                                $value = $a->getValue($field);
                                if ($value) {
                                    $ret .= ' - ' . $value->getText();
                                }
                            }
                        }
                    }
                    $params['object_name'] =  $ret;
                }
            }
        }
    }

    function permission_get_object_fullname($params) {
        $this->permission_get_object_name($params);
    }

    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            $allowed = array(
                Tracker::PERMISSION_ADMIN,
                Tracker::PERMISSION_FULL,
                Tracker::PERMISSION_SUBMITTER,
                Tracker::PERMISSION_SUBMITTER_ONLY,
                Tracker::PERMISSION_ASSIGNEE,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                'PLUGIN_TRACKER_FIELD_READ',
                'PLUGIN_TRACKER_FIELD_UPDATE',
                'PLUGIN_TRACKER_ARTIFACT_ACCESS',
                'PLUGIN_TRACKER_WORKFLOW_TRANSITION',
            );
            if (in_array($params['permission_type'], $allowed)) {
                $group_id  = $params['group_id'];
                $object_id = $params['object_id'];
                $type      = $this->getObjectTypeFromPermissions($params);
                if (!isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    switch ($type) {
                        case 'tracker':
                            if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $tracker->userIsAdmin();
                            }
                            break;
                        case 'field':
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $field->getTracker()->userIsAdmin();
                            }
                            break;
                        case 'artifact':
                            if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                                //TODO: manage permissions related to field "permission on artifact"
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $a->getTracker()->userIsAdmin();
                            }
                        case 'workflow transition':
                            if ($transition = TransitionFactory::instance()->getTransition($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $transition->getWorkflow()->getTracker()->userIsAdmin();
                            }
                            break;
                    }
                }
                if (isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    $params['allowed'] = $this->_cached_permission_user_allowed_to_change[$type][$object_id];
                }
            }
        }
    }

    public function get_available_reference_natures($params) {
        $natures = array(Tracker_Artifact::REFERENCE_NATURE => array('keyword' => 'artifact',
                                                                     'label'   => 'Artifact Tracker v5'));
        $params['natures'] = array_merge($params['natures'], $natures);
    }

    public function get_artifact_reference_group_id($params) {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
        if ($artifact) {
            $tracker = $artifact->getTracker();
            $params['group_id'] = $tracker->getGroupId();
        }
    }

    public function set_artifact_reference_group_id($params) {
        $reference = $params['reference'];
        if ($this->isDefaultReferenceUrl($reference)) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
            if ($artifact) {
                $tracker = $artifact->getTracker();
                $reference->setGroupId($tracker->getGroupId());
            }
        }
    }

    private function isDefaultReferenceUrl(Reference $reference) {
        return $reference->getLink() === TRACKER_BASE_URL. '/?&aid=$1&group_id=$group_id';
    }

    public function build_reference($params) {
        $row           = $params['row'];
        $params['ref'] = new Reference(
            $params['ref_id'],
            $row['keyword'],
            $row['description'],
            $row['link'],
            $row['scope'],
            $this->getServiceShortname(),
            Tracker_Artifact::REFERENCE_NATURE,
            $row['is_active'],
            $row['group_id']
        );
    }

    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event)
    {
        if ($event->getReference()->getServiceShortName() === self::SERVICE_SHORTNAME && $event->getReference()->getNature() === Tracker_Artifact::REFERENCE_NATURE) {
            $aid = (int) $event->getValue();
            if ($artifact = Tracker_ArtifactFactory::instance()->getArtifactById($aid)) {
                if ($artifact && $artifact->getTracker()->isActive()) {
                    $event->setOutput($artifact->fetchTooltip($event->getUser()));
                } else {
                    $event->setOutput($GLOBALS['Language']->getText('plugin_tracker_common_type', 'artifact_not_exist'));
                }
            }
        }
    }

    public function url_verification_instance($params)
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, $this->getPluginPath()) === 0 &&
            strpos($request_uri, $this->getPluginPath().'/notifications/') !== 0 &&
            strpos($request_uri, $this->getPluginPath().'/webhooks/') !== 0
        ) {
            $params['url_verification'] = new Tracker_URLVerification();
        }
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     * @param \Tuleap\Widget\Event\GetWidget $get_widget_event
     */
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event) {
        switch ($get_widget_event->getName()) {
            case Tracker_Widget_MyArtifacts::ID:
                $get_widget_event->setWidget(new Tracker_Widget_MyArtifacts());
                break;
            case Tracker_Widget_MyRenderer::ID:
                $get_widget_event->setWidget(new Tracker_Widget_MyRenderer());
                break;
            case Tracker_Widget_ProjectRenderer::ID:
                $get_widget_event->setWidget(new Tracker_Widget_ProjectRenderer());
                break;
        }
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = TRACKER_SERVICE_ICON;
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget(Tracker_Widget_MyArtifacts::ID);
        $event->addWidget(Tracker_Widget_MyRenderer::ID);
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget(Tracker_Widget_ProjectRenderer::ID);
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array(
            Tracker_Widget_MyArtifacts::ID,
            Tracker_Widget_MyRenderer::ID,
            Tracker_Widget_ProjectRenderer::ID
        ));
    }

    /** @see AtUserCreationDefaultWidgetsCreator::DEFAULT_WIDGETS_FOR_NEW_USER */
    public function default_widgets_for_new_user(array $params)
    {
        $params['widgets'][] = Tracker_Widget_MyArtifacts::ID;
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) {
        $injector = new Tracker_REST_ResourcesInjector();
        $injector->declareProjectPlanningResource($params['resources'], $params['project']);
    }

    function service_public_areas(GetPublicAreas $event)
    {
        $project = $event->getProject();
        if ($project->usesService($this->getServiceShortname())) {
            $tf = TrackerFactory::instance();

            // Get the artfact type list
            $trackers = $tf->getTrackersByGroupId($project->getGroupId());

            if ($trackers) {
                $entries  = array();
                $purifier = Codendi_HTMLPurifier::instance();
                foreach($trackers as $t) {
                    if ($t->userCanView()) {
                        $name      = $purifier->purify($t->name, CODENDI_PURIFIER_CONVERT_HTML);
                        $entries[] = '<a href="'. TRACKER_BASE_URL .'/?tracker='. $t->id .'">'. $name .'</a>';
                    }
                }
                if ($entries) {
                    $area = '';
                    $area .= '<a href="'. TRACKER_BASE_URL .'/?group_id='. $project->getGroupId() .'">';
                    $area .= '<i class="tuleap-services-angle-double-right tuleap-services-plugin_tracker tuleap-services-widget"></i>';
                    $area .= $GLOBALS['Language']->getText('plugin_tracker', 'service_lbl_key');
                    $area .= '</a>';

                    $area .= '<ul><li>'. implode('</li><li>', $entries) .'</li></ul>';

                    $event->addArea($area);
                }
            }
        }
    }

    public function project_creation_remove_legacy_services($params)
    {
        if (! $this->isRestricted()) {
            $this->getServiceActivator()->unuseLegacyService($params);
        }
    }

    public function project_registration_activate_service(ProjectRegistrationActivateService $event)
    {
        $this->getServiceActivator()->forceUsageOfService($event->getProject(), $event->getTemplate(), $event->getLegacy());
        $this->getReferenceCreator()->insertArtifactsReferencesFromLegacy($event->getProject());
    }

    /**
     * @return ReferenceCreator
     */
    private function getReferenceCreator()
    {
        return new ReferenceCreator(
            ServiceManager::instance(), TrackerV3::instance(), new ReferenceDao()
        );
    }

    /**
     * @return ServiceActivator
     */
    private function getServiceActivator()
    {
        return new ServiceActivator(ServiceManager::instance(), TrackerV3::instance(), new ServiceCreator());
    }

    /**
     * When a project is deleted, we delete all its trackers
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     *
     * @return void
     */
    public function project_is_deleted($params)
    {
        $group_id = $params['group_id'];
        if ($group_id) {
            EventManager::instance()->processEvent(new ProjectDeletionEvent($group_id));

            $tracker_manager = new TrackerManager();
            $tracker_manager->deleteProjectTrackers($group_id);
        }
    }

    public function display_deleted_trackers(array &$params)
    {
        $tracker_manager = new TrackerManager();
        $tracker_manager->displayDeletedTrackers();
    }

    /**
     * Process the nightly job to send reminder on artifact correponding to given criteria
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function codendi_daily_start($params) {
        include_once 'Tracker/TrackerManager.class.php';
        $trackerManager = new TrackerManager();
        $logger = new BackendLogger();
        $logger->debug("[TDR] Tuleap daily start event: launch date reminder");

        $this->getSystemEventManager()->createEvent(
            'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME,
            "",
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );

        $this->dailyCleanup();

        return $trackerManager->sendDateReminder();
    }

    /**
     * Fill the list of subEvents related to tracker in the project history interface
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function fillProjectHistorySubEvents($params) {
        array_push(
            $params['subEvents']['event_others'],
            'tracker_date_reminder_add',
            'tracker_date_reminder_edit',
            'tracker_date_reminder_delete',
            'tracker_date_reminder_sent',
            Tracker_FormElement::PROJECT_HISTORY_UPDATE,
            ArtifactDeletor::PROJECT_HISTORY_ARTIFACT_DELETED
        );
    }

    public function soap_description($params) {
        $params['end_points'][] = array(
            'title'       => 'Tracker',
            'wsdl'        => $this->getPluginPath().'/soap/?wsdl',
            'wsdl_viewer' => $this->getPluginPath().'/soap/view-wsdl.php',
            'changelog'   => $this->getPluginPath().'/soap/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../www/soap/VERSION'),
            'description' => 'Query and modify Trackers.',
        );
    }

    /**
     * @param array $params
     */
    public function agiledashboard_export_xml($params) {
        $can_bypass_threshold = true;
        $user_xml_exporter    = new UserXMLExporter(
            $this->getUserManager(),
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );

        $user    = UserManager::instance()->getCurrentUser();
        $archive = new NoArchive();

        $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
            ->exportToXml($params['project'], $params['into_xml'], $user);
    }

    /**
     * @return TrackerXmlExport
     */
    private function getTrackerXmlExport(UserXMLExporter $user_xml_exporter, $can_bypass_threshold)
    {
        $rng_validator           = new XML_RNGValidator();
        $artifact_link_usage_dao = new ArtifactLinksUsageDao();

        return new TrackerXmlExport(
            $this->getTrackerFactory(),
            $this->getTrackerFactory()->getTriggerRulesManager(),
            $rng_validator,
            new Tracker_Artifact_XMLExport(
                $rng_validator,
                $this->getArtifactFactory(),
                $can_bypass_threshold,
                $user_xml_exporter
            ),
            $user_xml_exporter,
            EventManager::instance(),
            new NaturePresenterFactory(new NatureDao(), $artifact_link_usage_dao),
            $artifact_link_usage_dao
        );
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function import_xml_project($params) {
        $import_spotter = Spotter::instance();
        $import_spotter->startImport();

        TrackerXmlImport::build($params['user_finder'], $params['logger'])->import(
            $params['configuration'],
            $params['project'],
            $params['xml_content'],
            $params['mappings_registery'],
            $params['extraction_path']
        );

        $import_spotter->endImport();
    }

    public function import_xml_is_project_valid($params)
    {
        if(! $this->checkNaturesExistsOnPlateform($params['xml_content'])) {
            $params['error'] = true;
        }
    }

    private function checkNaturesExistsOnPlateform(SimpleXMLElement $xml)
    {
        if(! $xml->trackers['use-natures'][0]) {
            return true;
        }

        if (! (array)$xml->natures) {
            return true;
        }

        $plateform_natures["nature"] = array(Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD);
        foreach($this->getNatureDao()->searchAll() as $nature) {
            $plateform_natures["nature"][] = $nature['shortname'];
        }

        $this->addCustomNatures($plateform_natures["nature"]);

        foreach ($xml->natures->nature as $nature) {
            if (! in_array((string)$nature, $plateform_natures['nature'])) {
                return false;
            }
        }

        return true;
    }

    private function addCustomNatures(array &$natures) {
        $params['natures'] = &$natures;
        EventManager::instance()->processEvent(
            Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES,
            $params
        );
    }

    private function getNatureDao()
    {
        return new NatureDao();
    }

    /**
     * @see Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT
     */
    public function collect_errors_without_importing_xml_project($params)
    {
        $tracker_xml_import = TrackerXmlImport::build($params['user_finder'], $params['logger']);
        $params['errors'] = $tracker_xml_import->collectErrorsWithoutImporting(
            $params['project'],
            $params['xml_content']
        );
    }

    public function user_manager_get_user_instance(array $params) {
        if ($params['row']['user_id'] == Tracker_Workflow_WorkflowUser::ID) {
            $params['user'] = new Tracker_Workflow_WorkflowUser($params['row']);
        }
    }
    public function plugin_statistics_service_usage($params) {

        $dao             = new Tracker_ArtifactDao();

        $start_date      = strtotime($params['start_date']);
        $end_date        = strtotime($params['end_date']);

        $number_of_open_artifacts_between_two_dates   = $dao->searchSubmittedArtifactBetweenTwoDates($start_date, $end_date);
        $number_of_closed_artifacts_between_two_dates = $dao->searchClosedArtifactBetweenTwoDates($start_date, $end_date);

        $params['csv_exporter']->buildDatas($number_of_open_artifacts_between_two_dates, "Trackers v5 - Opened Artifacts");
        $params['csv_exporter']->buildDatas($number_of_closed_artifacts_between_two_dates, "Trackers v5 - Closed Artifacts");
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) {
        $injector = new Tracker_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_GET_PROJECT_TRACKERS
     */
    public function rest_get_project_trackers($params) {
        $user             = UserManager::instance()->getCurrentUser();
        $tracker_resource = $this->buildRightVersionOfProjectTrackersResource($params['version']);
        $project          = $params['project'];

        $this->checkProjectRESTAccess($project, $user);

        $params['result'] = $tracker_resource->get(
            $user,
            $project,
            $params['representation'],
            $params['limit'],
            $params['offset']
        );
    }

    /**
     * @see REST_OPTIONS_PROJECT_TRACKERS
     */
    public function rest_options_project_trackers($params) {
        $user             = UserManager::instance()->getCurrentUser();
        $project          = $params['project'];
        $tracker_resource = $this->buildRightVersionOfProjectTrackersResource($params['version']);

        $this->checkProjectRESTAccess($project, $user);

        $params['result'] = $tracker_resource->options(
            $user,
            $project,
            $params['limit'],
            $params['offset']
        );
    }

    private function checkProjectRESTAccess(Project $project, PFUser $user) {
        $project_authorization_class = '\\Tuleap\\REST\\ProjectAuthorization';
        $project_authorization       = new $project_authorization_class();

        $project_authorization->userCanAccessProject($user, $project, new Tracker_URLVerification());
    }

    private function buildRightVersionOfProjectTrackersResource($version) {
        $class_with_right_namespace = '\\Tuleap\\Tracker\\REST\\'.$version.'\\ProjectTrackersResource';
        return new $class_with_right_namespace;
    }

    public function agiledashboard_event_rest_get_milestone($params) {
        if ($this->buildRightVersionOfMilestonesBurndownResource($params['version'])->hasBurndown($params['user'], $params['milestone'])) {
            $params['milestone_representation']->enableBurndown();
        }
    }

    public function agiledashboard_event_rest_options_burndown($params) {
        $this->buildRightVersionOfMilestonesBurndownResource($params['version'])->options($params['user'], $params['milestone']);
    }

    public function agiledashboard_event_rest_get_burndown($params) {
        $params['burndown'] = $this->buildRightVersionOfMilestonesBurndownResource($params['version'])->get($params['user'], $params['milestone']);
    }

     private function buildRightVersionOfMilestonesBurndownResource($version) {
        $class_with_right_namespace = '\\Tuleap\\Tracker\\REST\\'.$version.'\\MilestonesBurndownResource';
        return new $class_with_right_namespace;
    }

    private function getTrackerSystemEventManager() {
        return new Tracker_SystemEventManager($this->getSystemEventManager());
    }

    private function getSystemEventManager() {
        return SystemEventManager::instance();
    }

    private function getMigrationManager() {
        return new Tracker_Migration_MigrationManager(
            $this->getTrackerSystemEventManager(),
            $this->getTrackerFactory(),
            $this->getArtifactFactory(),
            $this->getTrackerFormElementFactory(),
            $this->getUserManager(),
            $this->getProjectManager()
        );
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    private function getTrackerFactory() {
        return TrackerFactory::instance();
    }

    private function getUserManager() {
        return UserManager::instance();
    }

    private function getTrackerFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }

    private function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }

    /**
     * @see Event::BACKEND_ALIAS_GET_ALIASES
     */
    public function backend_alias_get_aliases($params) {
        $config = new MailGatewayConfig(
            new MailGatewayConfigDao()
        );

        $src_dir  = ForgeConfig::get('codendi_dir');
        $script   = $src_dir .'/plugins/tracker/bin/emailgateway-wrapper.sh';

        $command = "sudo -u codendiadm $script";

        if ($config->isTokenBasedEmailgatewayEnabled() || $config->isInsecureEmailgatewayEnabled()) {
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE, "\"|$command\"");
        }

        if ($config->isInsecureEmailgatewayEnabled()) {
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION, "\"|$command\"");
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE, "\"|$command\"");
        }

    }
    public function get_projectid_from_url($params) {
        $url = $params['url'];
        if (strpos($url,'/plugins/tracker/') === 0) {
            if (! $params['request']->get('tracker')) {
                return;
            }

            $tracker = TrackerFactory::instance()->getTrackerById($params['request']->get('tracker'));
            if ($tracker) {
                $params['project_id'] = $tracker->getGroupId();
            }
        }
    }

    /** @see Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES */
    public function system_event_get_custom_queues(array $params) {
        $params['queues'][Tracker_SystemEvent_Tv3Tv5Queue::NAME] = new Tracker_SystemEvent_Tv3Tv5Queue();
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue($params) {
        if ($params['queue'] === Tracker_SystemEvent_Tv3Tv5Queue::NAME) {
            $params['types'][] = SystemEvent_TRACKER_V3_MIGRATION::NAME;
        }


    }

    public function system_event_get_types_for_default_queue($params) {
        $params['types'][] = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_DAILY::NAME;
        $params['types'][] = 'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME;
    }


    /** @see Event::SERVICES_TRUNCATED_EMAILS */
    public function services_truncated_emails(array $params) {
        $project = $params['project'];
        if ($project->usesService($this->getServiceShortname())) {
            $params['services'][] = $GLOBALS['Language']->getText('plugin_tracker', 'service_lbl_key');
        }
    }

    public function fulltextsearch_event_fetch_all_document_search_types($params) {
        $params['all_document_search_types'][] = array(
            'key'     => 'tracker',
            'name'    => $GLOBALS['Language']->getText('plugin_tracker', 'tracker_artifacts'),
            'info'    => $GLOBALS['Language']->getText('plugin_tracker', 'tracker_fulltextsearch_info'),
            'can_use' => false,
            'special' => true,
        );
    }

    public function fulltextsearch_event_fetch_project_tracker_fields($params) {
        $user     = $params['user'];
        $trackers = $this->getTrackerFactory()->getTrackersByGroupIdUserCanView($params['project_id'], $user);
        $fields   = $this->getTrackerFormElementFactory()->getUsedSearchableTrackerFieldsUserCanView($user, $trackers);

        $params['fields'] = $fields;
    }

    public function site_admin_configuration_tracker($params) {
        $label = $GLOBALS['Language']->getText('plugin_tracker', 'admin_tracker_template');

        $params['additional_entries'][] = '<a href="/plugins/tracker/?group_id=100" class="admin-sidebar-section-nav-item">'. $label .'</a>';
    }

    public function fulltextsearch_event_does_tracker_service_use_ugroup($params) {
        $dao        = new Tracker_PermissionsDao();
        $ugroup_id  = $params['ugroup_id'];
        $project_id = $params['project_id'];

        if ($dao->isThereAnExplicitPermission($ugroup_id, $project_id)) {
            $params['is_used'] = true;
            return;
        }

        if ($dao->doAllItemsHaveExplicitPermissions($project_id)) {
            $params['is_used'] = false;
            return;
        }

        $params['is_used'] = $dao->isThereADefaultPermissionThatUsesUgroup($ugroup_id);
    }

    public function export_xml_project($params) {
        if (! isset($params['options']['tracker_id']) && ! isset($params['options']['all'])) {
            return;
        }

        $project              = $params['project'];
        $can_bypass_threshold = $params['options']['force'] === true;
        $user_xml_exporter    = $params['user_xml_exporter'];
        $user                 = $params['user'];

        if ($params['options']['all'] === true) {
            $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
            ->exportToXmlFull($project, $params['into_xml'], $user, $params['archive']);

        } else if (isset($params['options']['tracker_id'])) {
            $this->exportSingleTracker($params, $project, $user_xml_exporter, $user, $can_bypass_threshold);
        }
    }

    private function exportSingleTracker(
        array $params,
        Project $project,
        UserXMLExporter $user_xml_exporter,
        PFUser $user,
        $can_bypass_threshold
    ) {
        $tracker_id = $params['options']['tracker_id'];
        $tracker    = $this->getTrackerFactory()->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new Exception ('Tracker ID does not exist');
        }

        if ($tracker->getGroupId() != $project->getID()) {
            throw new Exception ('Tracker ID does not belong to project ID');
        }

        $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
            ->exportSingleTrackerToXml($params['into_xml'], $tracker_id, $user, $params['archive']);
    }

    public function get_reference($params) {
        if ($this->isArtifactReferenceInMultipleTrackerServicesContext($params['keyword'])) {
            $artifact_id       = $params['value'];
            $keyword           = $params['keyword'];
            $reference_manager = $params['reference_manager'];

            $tracker_reference_manager = $this->getTrackerReferenceManager($reference_manager);

            $reference = $tracker_reference_manager->getReference(
                $keyword,
                $artifact_id
            );

            if ($reference) {
                $params['reference'] = $reference;
            }
        }
    }

    private function isArtifactReferenceInMultipleTrackerServicesContext($keyword) {
        return (TrackerV3::instance()->available() && ($keyword === 'art' || $keyword === 'artifact'));
    }

    /**
     * @return Tracker_ReferenceManager
     */
    private function getTrackerReferenceManager(ReferenceManager $reference_manager) {
        return new Tracker_ReferenceManager(
            $reference_manager,
            $this->getArtifactFactory()
        );
    }

    public function can_user_access_ugroup_info($params) {
        $project = $params['project'];
        $user    = $params['user'];

        $trackers = $this->getTrackerFactory()->getTrackersByGroupIdUserCanView($project->getID(), $user);
        foreach ($trackers as $tracker) {
            if ($tracker->hasFieldBindedToUserGroupsViewableByUser($user)) {
                $params['can_access'] = true;
                break;
            }
        }
    }

    /** @see TemplatePresenter::EVENT_ADDITIONAL_ADMIN_BUTTONS */
    public function event_additional_admin_buttons(array $params)
    {
        /** @var Project $template */
        $template = $params['template'];

        $is_service_used = $template->usesService($this->getServiceShortname());

        $params['buttons'][] = array(
            'icon'        => 'fa-list',
            'label'       => dgettext('tuleap-tracker', 'Configure trackers'),
            'uri'         => TRACKER_BASE_URL . '/?group_id=' . (int)$template->getID(),
            'is_disabled' => ! $is_service_used,
            'title'       => ! $is_service_used ? dgettext('tuleap-tracker', 'This template does not use trackers') : ''
        );
    }

    public function get_permission_delegation($params)
    {
        $permission = new TrackerAdminAllProjects();

        $params['plugins_permission'][TrackerAdminAllProjects::ID] = $permission;
    }

    public function project_admin_ugroup_deletion($params)
    {
        $project_id = $params['group_id'];
        $ugroup     = $params['ugroup'];

        $ugroups_to_notify_dao = new UgroupsToNotifyDao();
        $ugroups_to_notify_dao->deleteByUgroupId($project_id, $ugroup->getId());
    }

    public function project_admin_remove_user($params)
    {
        $project_id = $params['group_id'];
        $user_id    = $params['user_id'];

        $user_manager = UserManager::instance();

        $user    = $user_manager->getUserById($user_id);
        $project = $this->getProjectManager()->getProject($project_id);

        $cleaner = $this->getNotificationForProjectMemberCleaner();
        $cleaner->cleanNotificationsAfterUserRemoval($project, $user);
    }

    public function project_access_change($params)
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateProjectAccess($params['project_id'], $params['old_access'], $params['access']);
    }

    public function site_access_change(array $params)
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateSiteAccess($params['old_value']);
    }

    /**
     * @return UgroupsToNotifyUpdater
     */
    private function getUgroupToNotifyUpdater()
    {
        return new UgroupsToNotifyUpdater($this->getUgroupToNotifyDao());
    }

    /**
     * @return NotificationsForProjectMemberCleaner
     */
    private function getNotificationForProjectMemberCleaner() {
        return  new NotificationsForProjectMemberCleaner(
            $this->getTrackerFactory(),
            $this->getTrackerNotificationManager(),
            $this->getUserToNotifyDao()
        );
    }

    /**
     * @return UsersToNotifyDao
     */
    private function getUserToNotifyDao() {
        return new UsersToNotifyDao();
    }

    /**
     * @return UgroupsToNotifyDao
     */
    private function getUgroupToNotifyDao() {
        return new UgroupsToNotifyDao();
    }

    /**
     * @return Tracker_NotificationsManager
     */
    private function getTrackerNotificationManager() {
        $user_to_notify_dao             = $this->getUserToNotifyDao();
        $ugroup_to_notify_dao           = $this->getUgroupToNotifyDao();
        $unsubscribers_notification_dao = new UnsubscribersNotificationDAO;
        $notification_list_builder      = new NotificationListBuilder(
            new UGroupDao(),
            new CollectionOfUserInvolvedInNotificationPresenterBuilder($user_to_notify_dao, $unsubscribers_notification_dao),
            new CollectionOfUgroupToBeNotifiedPresenterBuilder($ugroup_to_notify_dao)
        );
        return new Tracker_NotificationsManager(
            $this,
            $notification_list_builder,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            new UserNotificationSettingsDAO,
            new GlobalNotificationsAddressesBuilder(),
            UserManager::instance(),
            new UGroupManager(),
            new GlobalNotificationSubscribersFilter($unsubscribers_notification_dao),
            new NotificationLevelExtractor(),
            new \TrackerDao(),
            new \ProjectHistoryDao(),
            $this->getForceUsageUpdater()
        );
    }

    /**
     * @see Event::USER_HISTORY
     */
    public function getRecentlyVisitedArtifacts(array $params)
    {
        /** @var PFUser $user */
        $user = $params['user'];

        $visit_retriever = new \Tuleap\Tracker\RecentlyVisited\VisitRetriever(
            new \Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao(),
            $this->getArtifactFactory(),
            new \Tuleap\Glyph\GlyphFinder(EventManager::instance())
        );
        $history_artifacts = $visit_retriever->getVisitHistory($user, HistoryRetriever::MAX_LENGTH_HISTORY);
        $params['history'] = array_merge($params['history'], $history_artifacts);
    }

    /**
     * @see Event::USER_HISTORY_CLEAR
     */
    public function clearRecentlyVisitedArtifacts(array $params)
    {
        /** @var PFUser $user */
        $user = $params['user'];

        $visit_cleaner = new \Tuleap\Tracker\RecentlyVisited\VisitCleaner(
            new \Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao()
        );
        $visit_cleaner->clearVisitedArtifacts($user);
    }

    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector)
    {
        $glyph_locations_collector->addLocation(
            'tuleap-tracker',
            new GlyphLocation(TRACKER_BASE_DIR . '/../glyphs')
        );
    }

    public function collect_heartbeats_entries(HeartbeatsEntryCollection $collection)
    {
        $collector = new LatestHeartbeatsCollector(
            new Tracker_ArtifactDao(),
            $this->getArtifactFactory(),
            new \Tuleap\Glyph\GlyphFinder(EventManager::instance()),
            $this->getUserManager(),
            UserHelper::instance()
        );
        $collector->collect($collection);
    }

    private function isInDashboard()
    {
        $current_page = new CurrentPage();

        return $current_page->isDashboard();
    }

    private function isInTrackerGlobalAdmin()
    {
        return strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL . '/?func=global-admin') === 0;
    }

    public function workerEvent(WorkerEvent $event)
    {
        $async_notifier = new \Tuleap\Tracker\Artifact\Changeset\Notification\AsynchronousNotifier();
        $async_notifier->addListener($event);
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager    = new UGroupManager();
        $presenter_builder = new ProjectAdminPermissionPerGroupPresenterBuilder(
            $ugroup_manager
        );

        $request            = HTTPRequest::instance();
        $selected_ugroup_id = $event->getSelectedUGroupId();
        $presenter          = $presenter_builder->buildPresenter(
            $request->getProject(),
            $selected_ugroup_id
        );

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(TRACKER_TEMPLATE_DIR . '/project-admin/')
            ->renderToString(
                'project-admin-permission-per-group',
                $presenter
            );

        $project         = $event->getProject();
        $rank_in_project = $project->getService(
            $this->getServiceShortname()
        )->getRank();

        $event->addPane($admin_permission_pane, $rank_in_project);
    }

    private function dailyCleanup()
    {
        $deletions_remover = new ArtifactsDeletionRemover(new ArtifactsDeletionDAO());
        $deletions_remover->deleteOutdatedArtifactsDeletions();
    }

    /**
     * @see \Tuleap\user\UserAutocompletePostSearchEvent
     */
    public function userAutocompletePostSearch(\Tuleap\user\UserAutocompletePostSearchEvent $event)
    {
        $additional_information = $event->getAdditionalInformation();
        if (! isset($additional_information['tracker_id'])) {
            return;
        }
        $tracker_factory = TrackerFactory::instance();
        $tracker         = $tracker_factory->getTrackerById($additional_information['tracker_id']);
        if ($tracker === null) {
            return;
        }

        $autocompleted_user_list                = $event->getUserList();
        $autocompleted_user_id_list             = [];
        foreach ($autocompleted_user_list as $autocompleted_user) {
            $autocompleted_user_id_list[] = $autocompleted_user['user_id'];
        }
        $global_notification_subscribers_filter = new GlobalNotificationSubscribersFilter(new UnsubscribersNotificationDAO);
        $autocompleted_user_id_list_filtered    = $global_notification_subscribers_filter->filterInvalidUserIDs(
            $tracker,
            $autocompleted_user_id_list
        );

        $autocompleted_user_list_filtered = [];
        foreach ($autocompleted_user_list as $autocompleted_user) {
            if (in_array($autocompleted_user['user_id'], $autocompleted_user_id_list_filtered)) {
                $autocompleted_user_list_filtered[] = $autocompleted_user;
            }
        }

        $event->setUserList($autocompleted_user_list_filtered);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup(TRACKER_BASE_URL, function(FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'],'[/[index.php]]',  function () {
                return new \Tuleap\Tracker\TrackerPluginDefaultController(new TrackerManager);
            });
            $r->get('/notifications/{id:\d+}/', function () {
                return new  NotificationsAdminSettingsDisplayController(
                    $this->getTrackerFactory(),
                    new TrackerManager,
                    $this->getUserManager()

                );
            });
            $r->post('/notifications/{id:\d+}/', function () {
                return new  NotificationsAdminSettingsUpdateController(
                    $this->getTrackerFactory(),
                    $this->getUserManager()
                );
            });
            $r->get('/notifications/my/{id:\d+}/', function () {
                return new  \Tuleap\Tracker\Notifications\Settings\NotificationsUserSettingsDisplayController(
                    TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/notifications/'),
                    $this->getTrackerFactory(),
                    new TrackerManager,
                    new UserNotificationSettingsRetriever(
                        new Tracker_GlobalNotificationDao,
                        new UnsubscribersNotificationDAO,
                        new UserNotificationOnlyStatusChangeDAO
                    )
                );
            });
            $r->post('/notifications/my/{id:\d+}/', function () {
                return new  \Tuleap\Tracker\Notifications\Settings\NotificationsUserSettingsUpdateController(
                    $this->getTrackerFactory(),
                    new UserNotificationSettingsDAO,
                    new ProjectHistoryDao
                );
            });

            $r->post('/webhooks/delete', function () {
                return new WebhookDeleteController(
                    new WebhookRetriever(new WebhookDao()),
                    $this->getTrackerFactory(),
                    new WebhookDao()
                );
            });

            $r->post('/webhooks/create', function () {
                return new WebhookCreateController(
                    new WebhookDao(),
                    $this->getTrackerFactory()
                );
            });

            $r->post(
                '/webhooks/edit',
                function () {
                    return new WebhookEditController(
                        new WebhookRetriever(new WebhookDao()),
                        TrackerFactory::instance(),
                        new WebhookDao()
                    );
            }
            );
        });
    }

    public function collectCLICommands(CLICommandsCollector $commands_collector)
    {
        $commands_collector->addCommand(
            new TrackerForceNotificationsLevelCommand(
                $this->getForceUsageUpdater(),
                ProjectManager::instance(),
                new NotificationLevelExtractor(),
                $this->getTrackerFactory(),
                new TrackerDao()
            )
        );
    }

    /**
     * @return NotificationsForceUsageUpdater
     */
    private function getForceUsageUpdater()
    {
        return new NotificationsForceUsageUpdater(
            new RecipientsManager(
                Tracker_FormElementFactory::instance(),
                UserManager::instance(),
                new UnsubscribersNotificationDAO(),
                new UserNotificationSettingsRetriever(
                    new Tracker_GlobalNotificationDao(),
                    new UnsubscribersNotificationDAO(),
                    new UserNotificationOnlyStatusChangeDAO()
                ),
                new UserNotificationOnlyStatusChangeDAO()
            ),
            new UserNotificationSettingsDAO()
        );
    }
}
