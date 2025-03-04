<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PermissionsManagerSavePermissionsSaveDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    protected PermissionsManager $permissions_manager;
    protected Project $project;
    protected string $permission_type;
    protected string $object_id;
    protected PermissionsDao&MockObject $permissions_dao;
    protected int $project_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project_id          = 404;
        $this->project             = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $this->permissions_dao     = $this->createMock(\PermissionsDao::class);
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = new PermissionsManager($this->permissions_dao);
        $ldai                      = $this->createMock(LegacyDataAccessInterface::class);
        $this->permissions_dao->method('getDa')->willReturn($ldai);
        $ldai->method('getErrorMessage');
    }

    protected function savePermissions($ugroups): void
    {
        $this->permissions_manager->savePermissions($this->project, $this->object_id, $this->permission_type, $ugroups);
    }

    public function testItThrowsExceptionWhenClearFailed(): void
    {
        $this->permissions_dao->method('clearPermission')->willReturn(false);

        self::expectException(\PermissionDaoException::class);

        $this->savePermissions([ProjectUGroup::PROJECT_MEMBERS, 104]);
    }

    public function testItThrowsExceptionWhenAddFailed(): void
    {
        $this->permissions_dao->method('clearPermission')->willReturn(true);
        $this->permissions_dao->method('addPermission')->willReturn(false);

        self::expectException(\PermissionDaoException::class);

        $this->savePermissions([ProjectUGroup::PROJECT_MEMBERS, 104]);
    }
}
