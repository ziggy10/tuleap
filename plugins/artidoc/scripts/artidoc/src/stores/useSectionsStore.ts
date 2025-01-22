/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { ComputedRef, Ref } from "vue";
import { computed, ref } from "vue";
import { isPendingSection } from "@/helpers/artidoc-section.type";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { extractSavedSectionsFromArtidocSections } from "@/helpers/extract-saved-sections-from-artidoc-sections";

export type StoredArtidocSection = ArtidocSection & InternalArtidocSectionId;

export interface SectionsStore {
    sections: Ref<StoredArtidocSection[]>;
    saved_sections: ComputedRef<readonly ArtidocSection[]>;
    getSectionPositionForSave: (section: ArtidocSection) => PositionForSection;
    replaceAll: (sections_collection: StoredArtidocSection[]) => void;
}

type BeforeSection = { before: string };
export type AtTheEnd = null;

export type PositionForSection = AtTheEnd | BeforeSection;
export interface InternalArtidocSectionId {
    internal_id: string;
}

export function buildSectionsStore(): SectionsStore {
    const sections: Ref<StoredArtidocSection[]> = ref([]);

    function replaceAll(sections_collection: StoredArtidocSection[]): void {
        sections.value = sections_collection;
    }

    const saved_sections: ComputedRef<readonly ArtidocSection[]> = computed(() => {
        return extractSavedSectionsFromArtidocSections(sections.value);
    });

    function getSectionPositionForSave(section: ArtidocSection): PositionForSection {
        const index = sections.value.findIndex((element) => element.id === section.id);
        if (index === -1) {
            return null;
        }

        if (index === sections.value.length - 1) {
            return null;
        }

        for (let i = index + 1; i < sections.value.length; i++) {
            if (!isPendingSection(sections.value[i])) {
                return { before: sections.value[i].id };
            }
        }

        return null;
    }

    return {
        sections,
        saved_sections,
        replaceAll,
        getSectionPositionForSave,
    };
}
