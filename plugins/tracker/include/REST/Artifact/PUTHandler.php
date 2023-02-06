<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_NoChangeException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentContentNotValidException;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AllLinksToLinksKeyValuesConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveReverseLinks;
use Tuleap\Tracker\Artifact\Link\HandleUpdateArtifact;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\FaultMapper;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class PUTHandler
{
    public function __construct(
        private FieldsDataBuilder $fields_data_builder,
        private ArtifactUpdater $artifact_updater,
        private RetrieveReverseLinks $reverse_links_retriever,
        private HandleUpdateArtifact $artifact_update_handler,
        private DBTransactionExecutor $transaction_executor,
        private CheckArtifactRestUpdateConditions $check_artifact_rest_update_conditions,
    ) {
    }

    /**
     * @param ArtifactValuesRepresentation[] $values
     * @throws RestException
     */
    public function handle(array $values, Artifact $artifact, \PFUser $submitter, ?NewChangesetCommentRepresentation $comment): void
    {
        try {
            $this->check_artifact_rest_update_conditions->checkIfArtifactUpdateCanBePerformedThroughREST($submitter, $artifact);
            $changeset_values        = $this->fields_data_builder->getFieldsDataOnUpdate($values, $artifact, $submitter);
            $reverse_link_collection = $changeset_values->getArtifactLinkValue()?->getSubmittedReverseLinks();

            if ($reverse_link_collection !== null && count($reverse_link_collection->links) > 0) {
                $stored_reverse_links = $this->reverse_links_retriever->retrieveReverseLinks($artifact, $submitter);
                $this->transaction_executor->execute(
                    function () use ($reverse_link_collection, $stored_reverse_links, $artifact, $submitter, $comment) {
                        $this->artifact_update_handler->addReverseLink($artifact, $submitter, $reverse_link_collection->differenceById($stored_reverse_links), $comment)
                                                      ->map(function () use ($artifact, $submitter, $reverse_link_collection, $stored_reverse_links, $comment) {
                                                        $this->artifact_update_handler->removeReverseLinks($artifact, $submitter, $stored_reverse_links->differenceById($reverse_link_collection), $comment);
                                                      })
                                              ->mapErr(
                                                  [FaultMapper::class, 'mapToRestException']
                                              );
                    }
                );
            } else {
                $values_with_links_key = AllLinksToLinksKeyValuesConverter::convertIfNeeded($values);
                $this->artifact_updater->update($submitter, $artifact, $values_with_links_key, $comment);
            }
        } catch (
            Tracker_FormElement_InvalidFieldException |
            Tracker_FormElement_InvalidFieldValueException |
            CommentContentNotValidException $exception
        ) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
    }
}
