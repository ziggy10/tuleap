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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\MilestoneRepository;

class MilestoneRepositoryAdapter implements MilestoneRepository
{
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var AdapterPermissions */
    private $adapter_permissions;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, AdapterPermissions $adapter_permissions)
    {
        $this->artifact_factory    = $artifact_factory;
        $this->adapter_permissions = $adapter_permissions;
    }

    public function findById(PFUser $current_user, int $id): ?Tracker_Artifact
    {
        $milestone = $this->artifact_factory->getArtifactById($id);
        if ($milestone === null) {
            return null;
        }
        if (! $this->adapter_permissions->canUserReadArtifact($current_user, $milestone)) {
            return null;
        }
        // TODO check $milestone is a milestone
        return $milestone;
    }
}
