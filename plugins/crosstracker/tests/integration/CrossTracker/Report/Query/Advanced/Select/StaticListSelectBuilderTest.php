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
use Tracker_FormElementFactory;
use Tuleap\CrossTracker\CrossTrackerDefaultReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class StaticListSelectBuilderTest extends CrossTrackerFieldTestCase
{
    /**
     * @var Tracker[]
     */
    private array $trackers;
    /**
     * @var array<int, StaticListValueRepresentation[]>
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

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

        $release_static_list_field_id = $tracker_builder->buildStaticListField(
            $release_tracker->getId(),
            'list_field',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE
        );
        $sprint_list_field_id         = $tracker_builder->buildStaticListField(
            $sprint_tracker->getId(),
            'list_field',
            Tracker_FormElementFactory::FIELD_OPEN_LIST_TYPE
        );

        $release_bind_ids     = $tracker_builder->buildValuesForStaticListField(
            $release_static_list_field_id,
            ['A110', 'A610']
        );
        $sprint_bind_list_ids = $tracker_builder->buildValuesForStaticListField(
            $sprint_list_field_id,
            ['Elan', 'Elise'],
        );
        $sprint_bind_open_ids = $tracker_builder->buildValuesForStaticOpenListField(
            $sprint_list_field_id,
            ['Europa'],
        );

        $tracker_builder->setReadPermission(
            $release_static_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id            = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_static_list_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_open_list_id    = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_list_changeset     = $tracker_builder->buildLastChangeset($release_artifact_with_static_list_id);
        $sprint_artifact_with_open_list_changeset = $tracker_builder->buildLastChangeset($sprint_artifact_with_open_list_id);

        $this->expected_values = [
            $release_artifact_empty_id            => [],
            $release_artifact_with_static_list_id => [new StaticListValueRepresentation('A110', null)],
            $sprint_artifact_with_open_list_id    => [
                new StaticListValueRepresentation('Elan', null),
                new StaticListValueRepresentation('Europa', null),
            ],
        ];

        $tracker_builder->buildListValue(
            $release_artifact_with_list_changeset,
            $release_static_list_field_id,
            $release_bind_ids['A110']
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_open_list_changeset,
            $sprint_list_field_id,
            $sprint_bind_list_ids['Elan']
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_open_list_changeset,
            $sprint_list_field_id,
            $sprint_bind_open_ids['Europa'],
            true
        );
    }

    private function getQueryResults(CrossTrackerDefaultReport $report, PFUser $user): CrossTrackerReportContentRepresentation
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0);
        assert($result instanceof CrossTrackerReportContentRepresentation);
        return $result;
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerDefaultReport(
                1,
                "SELECT list_field FROM @project = 'self' WHERE list_field = '' OR list_field != ''",
                $this->trackers,
                true,
            ),
            $this->user,
        );

        self::assertSame(3, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('list_field', $result->selected[1]->name);
        self::assertSame('list_static', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('list_field', $artifact);
            $value = $artifact['list_field'];
            self::assertInstanceOf(StaticListRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(array_values($this->expected_values), $values);
    }
}
