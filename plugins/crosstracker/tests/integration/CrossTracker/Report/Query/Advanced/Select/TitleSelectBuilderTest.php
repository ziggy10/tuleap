<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

use Codendi_HTMLPurifier;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class TitleSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private PFUser $user;
    /**
     * @var Tracker[]
     */
    private array $trackers;
    /**
     * @var array<int, string>
     */
    private array $expected_results;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject();
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

        $release_text_field_id = $tracker_builder->buildTextField(
            $release_tracker->getId(),
            'text_field',
        );
        $tracker_builder->buildTitleSemantic($release_tracker->getId(), $release_text_field_id);
        $sprint_text_field_id = $tracker_builder->buildTextField(
            $sprint_tracker->getId(),
            'text_field',
        );
        $tracker_builder->buildTitleSemantic($sprint_tracker->getId(), $sprint_text_field_id);

        $tracker_builder->setReadPermission(
            $release_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_with_text_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_text_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $release_artifact_with_text_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_text_id);
        $sprint_artifact_with_text_changeset  = $tracker_builder->buildLastChangeset($sprint_artifact_with_text_id);

        $commonmark_interpreter = CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance());

        $this->expected_results = [
            $release_artifact_with_text_id => 'Hello World!',
            $sprint_artifact_with_text_id  => $commonmark_interpreter->getInterpretedContentWithReferences('**Title**', $project_id),
        ];
        $tracker_builder->buildTextValue(
            $release_artifact_with_text_changeset,
            $release_text_field_id,
            $this->expected_results[$release_artifact_with_text_id],
            'text'
        );
        $tracker_builder->buildTextValue(
            $sprint_artifact_with_text_changeset,
            $sprint_text_field_id,
            '**Title**',
            'commonmark'
        );
    }

    private function getQueryResults(CrossTrackerReport $report, PFUser $user): CrossTrackerReportContentRepresentation
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0, false);
        assert($result instanceof CrossTrackerReportContentRepresentation);
        return $result;
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerReport(
                1,
                "SELECT @title WHERE @title = '' OR @title != ''",
                $this->trackers,
            ),
            $this->user,
        );
        self::assertSame(2, $result->getTotalSize());
        self::assertCount(1, $result->selected);
        self::assertSame('@title', $result->selected[0]->name);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(1, $artifact);
            self::assertArrayHasKey('@title', $artifact);
            $value = $artifact['@title'];
            self::assertInstanceOf(TextResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(
            array_values($this->expected_results),
            $values
        );
    }
}
