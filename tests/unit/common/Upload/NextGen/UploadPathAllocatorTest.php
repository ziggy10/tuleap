<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Upload\NextGen;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class UploadPathAllocatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testTheSamePathIsAlwaysAllocatedForAGivenItemID(): void
    {
        $allocator = new UploadPathAllocator('/var/tmp');

        $file_id = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        self::assertSame(
            $allocator->getPathForItemBeingUploaded(new FileBeingUploadedInformation($file_id, 'Filename', 123, 0)),
            $allocator->getPathForItemBeingUploaded(new FileBeingUploadedInformation($file_id, 'Filename', 123, 0))
        );
    }
}
