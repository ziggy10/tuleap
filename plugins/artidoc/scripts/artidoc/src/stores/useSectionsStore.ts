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

import type { Ref } from "vue";
import { ref, provide } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { sectionsStoreKey } from "./sectionsStoreKey";
import { isArtifactSection } from "@/helpers/artidoc-section.type";
import type {
    ArtidocSection,
    PendingArtifactSection,
    ArtifactSection,
} from "@/helpers/artidoc-section.type";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { getAllSections } from "@/helpers/rest-querier";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { Tracker } from "@/stores/configuration-store";
import { isTrackerWithSubmittableSection } from "@/stores/configuration-store";
import { okAsync } from "neverthrow";

export interface SectionsStore {
    sections: Ref<readonly ArtidocSection[] | undefined>;
    is_sections_loading: Ref<boolean>;
    loadSections: (
        item_id: number,
        tracker: Tracker | null,
        can_user_edit_document: boolean,
    ) => void;
    updateSection: (section: ArtifactSection) => void;
    insertSection: (section: PendingArtifactSection, position: Position) => void;
    removeSection: (section: ArtidocSection, tracker: Tracker | null) => void;
    insertPendingArtifactSectionForEmptyDocument: (tracker: Tracker | null) => void;
}

type BeforeSection = { index: number };
type AtTheEnd = "at-the-end";

export const AT_THE_END: AtTheEnd = "at-the-end";
export type Position = AtTheEnd | BeforeSection;

export function useSectionsStore(): SectionsStore {
    const skeleton_data = [
        ArtifactSectionFactory.create(),
        ArtifactSectionFactory.create(),
        ArtifactSectionFactory.create(),
    ];
    const sections: Ref<ArtidocSection[] | undefined> = ref(skeleton_data);
    const is_sections_loading = ref(true);

    function loadSections(
        item_id: number,
        tracker: Tracker | null,
        can_user_edit_document: boolean,
    ): void {
        getAllSections(item_id)
            .andThen((artidoc_sections: readonly ArtidocSection[]) => {
                sections.value = [...artidoc_sections];

                if (sections.value.length === 0 && can_user_edit_document) {
                    insertPendingArtifactSectionForEmptyDocument(tracker);
                }

                return okAsync(true);
            })
            .match(
                () => {
                    is_sections_loading.value = false;
                },
                () => {
                    sections.value = undefined;
                    is_sections_loading.value = false;
                },
            );
    }

    function updateSection(section: ArtifactSection): void {
        if (sections.value === undefined) {
            throw Error("Unexpected call to updateSection while there is no section");
        }

        const length = sections.value.length;
        for (let i = 0; i < length; i++) {
            const current = sections.value[i];
            if (isArtifactSection(current) && current.id === section.id) {
                sections.value[i] = section;
            }
        }
    }

    function insertSection(section: PendingArtifactSection, position: Position): void {
        if (sections.value === undefined) {
            return;
        }

        if (position === AT_THE_END) {
            sections.value.push(section);
        } else {
            sections.value.splice(position.index, 0, section);
        }
    }

    function insertPendingArtifactSectionForEmptyDocument(tracker: Tracker | null): void {
        if (!tracker) {
            return;
        }

        if (!isTrackerWithSubmittableSection(tracker)) {
            return;
        }

        if (sections.value === undefined) {
            return;
        }
        if (sections.value.length > 0) {
            return;
        }

        sections.value.push(PendingArtifactSectionFactory.overrideFromTracker(tracker));
    }

    function removeSection(section: ArtidocSection, tracker: Tracker | null): void {
        if (sections.value === undefined) {
            return;
        }

        const index = sections.value.findIndex((element) => element.id === section.id);
        if (index === -1) {
            return;
        }

        sections.value.splice(index, 1);

        insertPendingArtifactSectionForEmptyDocument(tracker);
    }

    return {
        sections,
        is_sections_loading,
        loadSections,
        updateSection,
        insertSection,
        removeSection,
        insertPendingArtifactSectionForEmptyDocument,
    };
}

let sectionsStore: SectionsStore | null = null;

export function provideSectionsStore(): SectionsStore {
    if (!sectionsStore) {
        sectionsStore = useSectionsStore();
        provide(sectionsStoreKey, sectionsStore);
    }

    return sectionsStore;
}

export function useInjectSectionsStore(): SectionsStore {
    const store = strictInject(sectionsStoreKey);
    if (!store) {
        throw new Error("No sections store provided!");
    }

    return store;
}
