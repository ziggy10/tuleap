<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

final class TrackerPresenter
{
    /** @var int */
    public $id;
    /** @var bool */
    public $can_update_mapped_field;
    /** @var TitleFieldPresenter|null */
    public $title_field;
    /** @var int | null */
    public $add_in_place_tracker_id;

    public function __construct(
        TaskboardTracker $tracker,
        bool $can_update_mapped_field,
        ?TitleFieldPresenter $title_field,
        ?\Tracker $add_in_place_tracker
    ) {
        $this->id                      = $tracker->getTrackerId();
        $this->can_update_mapped_field = $can_update_mapped_field;
        $this->title_field             = $title_field;
        $this->add_in_place_tracker_id = ($add_in_place_tracker !== null) ? $add_in_place_tracker->getId() : null;
    }
}
