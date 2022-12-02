/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { okAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { SearchArtifacts } from "../../src/domain/fields/link-field/SearchArtifacts";
import type { LinkableArtifact } from "../../src/domain/fields/link-field/LinkableArtifact";

export const SearchArtifactsStub = {
    withoutResults: (): SearchArtifacts => ({
        searchArtifacts: () => okAsync([]),
    }),

    withResults: (entries: ResultAsync<readonly LinkableArtifact[], never>): SearchArtifacts => ({
        searchArtifacts: () => entries,
    }),
};
