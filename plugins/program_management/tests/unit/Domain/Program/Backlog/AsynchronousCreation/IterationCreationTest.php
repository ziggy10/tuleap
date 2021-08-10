<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPendingIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class IterationCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID              = 101;
    private const PROGRAM_INCREMENT_ID = 54;
    private const FIRST_ITERATION_ID   = 573;
    private const SECOND_ITERATION_ID  = 268;
    private const FIRST_CHANGESET_ID   = 4021;
    private const SECOND_CHANGESET_ID  = 4997;
    private UserIdentifier $user;
    private JustLinkedIterationCollection $just_linked_iterations;
    private RetrieveLastChangesetStub $changeset_retriever;
    private TestLogger $logger;
    private SearchPendingIterationStub $iteration_searcher;
    private VerifyIsUserStub $user_verifier;
    private VerifyIsIterationStub $iteration_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private RetrieveUserStub $user_retriever;
    private CheckProgramIncrementStub $program_increment_checker;

    protected function setUp(): void
    {
        $user                         = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $this->user                   = UserIdentifierStub::withId(self::USER_ID);
        $program_increment            = ProgramIncrementIdentifier::fromId(
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            self::PROGRAM_INCREMENT_ID,
            $user
        );
        $iterations                   = IterationIdentifier::buildCollectionFromProgramIncrement(
            SearchIterationsStub::withIterationIds(self::FIRST_ITERATION_ID, self::SECOND_ITERATION_ID),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $program_increment,
            $this->user
        );
        $this->just_linked_iterations = JustLinkedIterationCollection::fromIterations(
            VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
            $program_increment,
            ...$iterations
        );
        $this->changeset_retriever    = RetrieveLastChangesetStub::withLastChangesetIds(
            self::FIRST_CHANGESET_ID,
            self::SECOND_CHANGESET_ID
        );
        $this->logger                 = new TestLogger();

        $this->iteration_searcher        = SearchPendingIterationStub::withRow(
            self::FIRST_ITERATION_ID,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::FIRST_CHANGESET_ID
        );
        $this->user_verifier             = VerifyIsUserStub::withValidUser();
        $this->iteration_verifier        = VerifyIsIterationStub::withValidIteration();
        $this->visibility_verifier       = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->user_retriever            = RetrieveUserStub::withUser($user);
        $this->program_increment_checker = CheckProgramIncrementStub::buildProgramIncrementChecker();
    }

    public function testItRetrievesLastChangesetOfEachIterationAndBuildsCollection(): void
    {
        [$first_creation, $second_creation] = IterationCreation::buildCollectionFromJustLinkedIterations(
            $this->changeset_retriever,
            $this->logger,
            $this->just_linked_iterations,
            $this->user
        );
        self::assertSame(self::FIRST_ITERATION_ID, $first_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $first_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $first_creation->user->getId());
        self::assertSame(self::FIRST_CHANGESET_ID, $first_creation->changeset->id);

        self::assertSame(self::SECOND_ITERATION_ID, $second_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $second_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $second_creation->user->getId());
        self::assertSame(self::SECOND_CHANGESET_ID, $second_creation->changeset->id);
    }

    public function testItSkipsIterationWhenItHasNoLastChangeset(): void
    {
        $this->changeset_retriever = RetrieveLastChangesetStub::withNoLastChangeset();

        self::assertEmpty(
            IterationCreation::buildCollectionFromJustLinkedIterations(
                $this->changeset_retriever,
                $this->logger,
                $this->just_linked_iterations,
                $this->user
            )
        );
        self::assertTrue(
            $this->logger->hasErrorThatMatches('/Could not retrieve last changeset of iteration #[0-9]+, skipping it$/')
        );
    }

    public function testItBuildsFromStorage(): void
    {
        $iteration_creation = IterationCreation::fromStorage(
            $this->iteration_searcher,
            $this->user_verifier,
            $this->iteration_verifier,
            $this->visibility_verifier,
            $this->user_retriever,
            $this->program_increment_checker,
            self::FIRST_ITERATION_ID,
            self::USER_ID
        );
        self::assertSame(self::FIRST_ITERATION_ID, $iteration_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $iteration_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $iteration_creation->user->getId());
        self::assertSame(self::FIRST_CHANGESET_ID, $iteration_creation->changeset->id);
    }

    public function testItReturnsNullWhenStoredIterationCreationIsNotValid(): void
    {
        // It can happen if the Iteration artifact or the Program Increment artifact are deleted
        // between storage and processing.
        $iteration_searcher = SearchPendingIterationStub::withNoRow();
        self::assertNull(
            IterationCreation::fromStorage(
                $iteration_searcher,
                $this->user_verifier,
                $this->iteration_verifier,
                $this->visibility_verifier,
                $this->user_retriever,
                $this->program_increment_checker,
                self::FIRST_ITERATION_ID,
                self::USER_ID
            )
        );
    }

    public function testItReturnsNullWhenStoredUserIsNotValid(): void
    {
        // It's not supposed to happen as users cannot be deleted in Tuleap. They change status.
        self::assertNull(
            IterationCreation::fromStorage(
                $this->iteration_searcher,
                VerifyIsUserStub::withNotValidUser(),
                $this->iteration_verifier,
                $this->visibility_verifier,
                $this->user_retriever,
                $this->program_increment_checker,
                self::FIRST_ITERATION_ID,
                self::USER_ID
            )
        );
    }

    public function testItThrowsWhenStoredIterationIsNotValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Iteration tracker.
        $this->expectException(StoredIterationNoLongerValidException::class);
        IterationCreation::fromStorage(
            $this->iteration_searcher,
            $this->user_verifier,
            VerifyIsIterationStub::withNotIteration(),
            $this->visibility_verifier,
            $this->user_retriever,
            $this->program_increment_checker,
            self::FIRST_ITERATION_ID,
            self::USER_ID
        );
    }

    public function testItThrowsWhenStoredProgramIncrementIsNotValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        $this->expectException(StoredProgramIncrementNoLongerValidException::class);
        IterationCreation::fromStorage(
            $this->iteration_searcher,
            $this->user_verifier,
            $this->iteration_verifier,
            $this->visibility_verifier,
            $this->user_retriever,
            CheckProgramIncrementStub::buildOtherArtifactChecker(),
            self::FIRST_ITERATION_ID,
            self::USER_ID
        );
    }
}
