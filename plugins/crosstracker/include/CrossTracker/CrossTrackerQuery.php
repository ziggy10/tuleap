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

namespace Tuleap\CrossTracker;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\UUID;

final readonly class CrossTrackerQuery
{
    public function __construct(
        private UUID $uuid,
        private string $query,
        private string $title,
        private string $description,
        private int $widget_id,
    ) {
    }

    public static function fromTqlQueryAndWidgetId(string $tql_query, int $widget_id): self
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        return new self($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $tql_query, '', '', $widget_id);
    }

    public function getUUID(): UUID
    {
        return $this->uuid;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getWidgetId(): int
    {
        return $this->widget_id;
    }
}
