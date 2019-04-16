<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyFieldDetector;

class PermissionsExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface
     */
    private $read_only_field_detector;

    /**
     * @var PermissionsExporter
     */
    private $permissions_exporter;

    protected function setUp() : void
    {
        $this->read_only_field_detector = \Mockery::mock(ReadOnlyFieldDetector::class);
        $this->permissions_exporter     = new PermissionsExporter(
            $this->read_only_field_detector
        );
    }

    public function testItDoesNotComputePermissionsIfFormElementIsNotField()
    {
        $initial_permissions = [Tracker_FormElement::REST_PERMISSION_READ, Tracker_FormElement::REST_PERMISSION_UPDATE];
        $form_element        = \Mockery::mock(Tracker_FormElement::class);
        $form_element
            ->shouldReceive('exportCurrentUserPermissionsToREST')
            ->andReturn($initial_permissions);

        $user         = \Mockery::mock(\PFUser::class);
        $artifact     = \Mockery::mock(\Tracker_Artifact::class);

        $this->read_only_field_detector->shouldNotReceive('isFieldReadOnly');

        $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );
    }

    public function testItDoesNotChangePermissionsIfNotReadOnlyField()
    {
        $initial_permissions = [Tracker_FormElement::REST_PERMISSION_READ, Tracker_FormElement::REST_PERMISSION_UPDATE];
        $form_element        = \Mockery::mock(\Tracker_FormElement_Field::class);
        $form_element
            ->shouldReceive('exportCurrentUserPermissionsToREST')
            ->andReturn($initial_permissions);

        $user         = \Mockery::mock(\PFUser::class);
        $artifact     = \Mockery::mock(\Tracker_Artifact::class);

        $this->read_only_field_detector
            ->shouldReceive('isFieldReadOnly')
            ->andReturnFalse();

        $computed_permissions = $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );

        $this->assertCount(0, array_diff($initial_permissions, $computed_permissions));
    }

    public function testItShouldRemoveTheUpdatePermissionIfFieldIsReadOnly()
    {
        $initial_permissions = [Tracker_FormElement::REST_PERMISSION_READ, Tracker_FormElement::REST_PERMISSION_UPDATE];
        $form_element        = \Mockery::mock(\Tracker_FormElement_Field::class);
        $form_element
            ->shouldReceive('exportCurrentUserPermissionsToREST')
            ->andReturn($initial_permissions);

        $user         = \Mockery::mock(\PFUser::class);
        $artifact     = \Mockery::mock(\Tracker_Artifact::class);

        $this->read_only_field_detector
            ->shouldReceive('isFieldReadOnly')
            ->andReturnTrue();

        $computed_permissions = $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );

        $this->assertEquals(1, count($computed_permissions));
        $this->assertContains(Tracker_FormElement::REST_PERMISSION_READ, $computed_permissions);
        $this->assertNotContains(Tracker_FormElement::REST_PERMISSION_UPDATE, $computed_permissions);
    }
}
