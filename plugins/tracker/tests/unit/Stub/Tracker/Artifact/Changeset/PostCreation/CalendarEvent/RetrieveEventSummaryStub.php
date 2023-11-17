<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventSummary;

final class RetrieveEventSummaryStub implements RetrieveEventSummary
{
    private function __construct(private readonly Ok|Err $result)
    {
    }

    public static function withSummary(string $summary): self
    {
        return new self(Result::ok($summary));
    }

    public static function withError(string $message): self
    {
        return new self(Result::err($message));
    }

    public function getEventSummary(
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        bool $should_check_permissions,
    ): Ok|Err {
        return $this->result;
    }
}
