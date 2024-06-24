<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config\Command;

use Cardwall_OnTop_ColumnDao;
use Cardwall_OnTop_Config_Command_UpdateColumns;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class Cardwall_OnTop_Config_Command_UpdateColumnsTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $tracker_id;
    private Cardwall_OnTop_ColumnDao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_UpdateColumns $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;

        $tracker = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->dao     = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_UpdateColumns($tracker, $this->dao);
    }

    public function testItUpdatesAllColumns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            [
                12 => ['label' => 'Todo', 'bgcolor' => '#000000'],
                13 => ['label' => ''],
                14 => ['label' => 'Done', 'bgcolor' => '#16ed9d'],
            ]
        );
        $this->dao->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                [$this->tracker_id, 12, 'Todo', 0, 0, 0],
                [$this->tracker_id, 14, 'Done', 22, 237, 157],
            );
        $this->command->execute($request);
    }
}
