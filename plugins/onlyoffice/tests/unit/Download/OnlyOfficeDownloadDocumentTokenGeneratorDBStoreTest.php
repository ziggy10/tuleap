<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Download;

use DateInterval;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\OnlyOffice\Open\DocmanFileLastVersion;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeDownloadDocumentTokenGeneratorDBStoreTest extends TestCase
{
    public function testTokenIsGeneratedAndStored(): void
    {
        $dao             = $this->createMock(OnlyOfficeDownloadDocumentTokenDAO::class);
        $token_generator = new OnlyOfficeDownloadDocumentTokenGeneratorDBStore(
            $dao,
            new SplitTokenVerificationStringHasher(),
            new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentDownload()),
            new DateInterval('PT10S')
        );

        $user = UserTestBuilder::buildWithDefaults();
        $item = new \Docman_Item(['item_id' => 258]);

        $dao->expects(self::once())->method('create')->with($user->getId(), $item->getId(), self::anything(), 20)->willReturn(147);

        $token = $token_generator->generateDownloadToken(
            $user,
            new DocmanFileLastVersion($item, new \Docman_Version()),
            new \DateTimeImmutable('@10'),
        );

        self::assertStringContainsString('147', $token->getString());
    }
}
