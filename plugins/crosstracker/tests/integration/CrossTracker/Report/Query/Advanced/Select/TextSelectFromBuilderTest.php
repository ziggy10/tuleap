<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerExpertReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class TextSelectFromBuilderTest extends CrossTrackerFieldTestCase
{
    /**
     * @var array<int, ?string>
     */
    private array $expected_values;
    private PFUser $user;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_text_field_id = $tracker_builder->buildTextField(
            $release_tracker->getId(),
            'text_field',
        );
        $sprint_text_field_id  = $tracker_builder->buildTextField(
            $sprint_tracker->getId(),
            'text_field',
        );

        $tracker_builder->grantReadPermissionOnField(
            $release_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id     = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_text_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_text_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_text_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_text_id);
        $sprint_artifact_with_text_changeset  = $tracker_builder->buildLastChangeset($sprint_artifact_with_text_id);

        $commonmark_interpreter = CommonMarkInterpreter::build(\Codendi_HTMLPurifier::instance());

        $this->expected_values = [
            $release_artifact_with_text_id => '911 GT3 RS',
            $sprint_artifact_with_text_id  => $commonmark_interpreter->getInterpretedContentWithReferences('718 Cayman GT4 RS', $project_id),
        ];
        $tracker_builder->buildTextValue(
            $release_artifact_with_text_changeset,
            $release_text_field_id,
            $this->expected_values[$release_artifact_with_text_id],
            'text'
        );

        $tracker_builder->buildTextValue(
            $sprint_artifact_with_text_changeset,
            $sprint_text_field_id,
            $this->expected_values[$sprint_artifact_with_text_id],
            'commonmark'
        );
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerExpertReport(
                1,
                "SELECT text_field FROM @project = 'self' WHERE text_field = '' OR text_field != ''",
                '',
                '',
            ),
            $this->user,
        );
        self::assertSame(2, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('text_field', $result->selected[1]->name);
        self::assertSame('text', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('text_field', $artifact);
            $value = $artifact['text_field'];
            self::assertInstanceOf(TextResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(
            array_values($this->expected_values),
            $values
        );
    }
}
