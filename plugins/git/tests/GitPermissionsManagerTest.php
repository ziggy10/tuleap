<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

class GitPermissionsManagerTest extends TuleapTestCase {

    private $permissions_manager;
    private $git_permissions_manager;
    private $git_permissions_dao;
    private $git_system_event_manager;

    public function setUp() {
        parent::setUp();
        $this->permissions_manager = mock('PermissionsManager');
        PermissionsManager::setInstance($this->permissions_manager);
        $this->git_permissions_dao      = mock('Git_PermissionsDao');
        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $this->git_permissions_manager  = new GitPermissionsManager($this->git_permissions_dao, $this->git_system_event_manager);
    }

    public function tearDown() {
        parent::tearDown();
        PermissionsManager::clearInstance();
    }

    public function testWhenSwitchingFromAnonymousToRegularItUpdatesAllProjectsThatWereUsingAnonymous() {
        stub($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->returnsDar(array('group_id' => 101), array('group_id' => 104));

        expect($this->git_permissions_dao)->updateAllAnonymousRepositoriesToRegistered()->once();

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(101, 104))->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromAnonymousToRegularItDoesNothingWhenNoProjectsWereUsingAnonymous() {
        stub($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->returnsEmptyDar();

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();
        expect($this->git_permissions_dao)->updateAllAnonymousRepositoriesToRegistered()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToAnonymousItDoesNothing() {
        expect($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->never();
        expect($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->never();
        expect($this->git_permissions_dao)->updateAllAnonymousRepositoriesToRegistered()->never();
        expect($this->git_permissions_dao)->updateAllAuthenticatedRepositoriesToRegistered()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromAnonymousToRestrictedItUpdatesAllProjectsThatWereUsingAnonymous() {
        stub($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->returnsDar(array('group_id' => 101), array('group_id' => 104));

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(101, 104))->once();
        expect($this->git_permissions_dao)->updateAllAnonymousRepositoriesToRegistered()->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::ANONYMOUS, ForgeAccess::RESTRICTED);
    }

    public function testWhenSwitchingFromRestrictedToAnonymousItUpdatesAllProjectThatWereUsingUnRestricted() {
        stub($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->returnsDar(array('group_id' => 102), array('group_id' => 107));

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102, 107))->once();
        expect($this->git_permissions_dao)->updateAllAuthenticatedRepositoriesToRegistered()->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }

    public function testWhenSwitchingFromRestrictedToRegularItUpdatesAllProjectThatWereUsingUnRestricted() {
        stub($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->returnsDar(array('group_id' => 102), array('group_id' => 107));

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate(array(102, 107))->once();
        expect($this->git_permissions_dao)->updateAllAuthenticatedRepositoriesToRegistered()->once();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRestrictedToRegularItDoesNothingWhenNoProjectsWereUsingAuthenticated() {
        stub($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->returnsEmptyDar();

        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();
        expect($this->git_permissions_dao)->updateAllAuthenticatedRepositoriesToRegistered()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::RESTRICTED, ForgeAccess::REGULAR);
    }

    public function testWhenSwitchingFromRegularToRestrictedItDoesNothing() {
        expect($this->git_permissions_dao)->getAllProjectsWithAnonymousRepositories()->never();
        expect($this->git_permissions_dao)->getAllProjectsWithUnrestrictedRepositories()->never();
        expect($this->git_permissions_dao)->updateAllAnonymousRepositoriesToRegistered()->never();
        expect($this->git_permissions_dao)->updateAllAuthenticatedRepositoriesToRegistered()->never();
        expect($this->git_system_event_manager)->queueProjectsConfigurationUpdate()->never();

        $this->git_permissions_manager->updateSiteAccess(ForgeAccess::REGULAR, ForgeAccess::RESTRICTED);
    }
}
