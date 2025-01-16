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

import { describe, beforeEach, it, vi, expect } from "vitest";
import type { SectionsStore } from "@/stores/useSectionsStore";
import { AT_THE_END, buildSectionsStore } from "@/stores/useSectionsStore";
import * as rest from "@/helpers/rest-querier";
import { okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import type { Tracker } from "@/stores/configuration-store";
import type {
    ArtidocSection,
    FreetextSection,
    SectionBasedOnArtifact,
} from "@/helpers/artidoc-section.type";
import { isPendingFreetextSection, isPendingArtifactSection } from "@/helpers/artidoc-section.type";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { CreateStoredSections } from "@/stores/CreateStoredSections";

describe("buildSectionsStore", () => {
    let can_user_edit_document: boolean, selected_tracker: Tracker | null;

    beforeEach(() => {
        can_user_edit_document = true;
        selected_tracker = null;
    });

    const getEmptyStore = (): SectionsStore =>
        buildSectionsStore(can_user_edit_document, selected_tracker);

    const getStoreWithSections = (sections: ArtidocSection[]): SectionsStore => {
        const store = buildSectionsStore(can_user_edit_document, selected_tracker);
        store.replaceAll(CreateStoredSections.fromArtidocSectionsCollection(sections));
        return store;
    };

    it("should have no sections by default", () => {
        const store = getEmptyStore();

        expect(store.sections.value).toHaveLength(0);
    });

    describe("replaceAll", () => {
        it("should store loaded sections", () => {
            const store = getEmptyStore();

            store.replaceAll(
                CreateStoredSections.fromArtidocSectionsCollection([
                    ArtifactSectionFactory.create(),
                    FreetextSectionFactory.create(),
                ]),
            );

            expect(store.sections.value).toHaveLength(2);
        });

        it("should create an internal id because when section are replaced (pending section -> artifact section) the fake id is replaced by the real one and it could mess up the v-for.key", () => {
            const store = getEmptyStore();
            const section = ArtifactSectionFactory.create();

            store.replaceAll([CreateStoredSections.fromArtidocSection(section)]);

            expect(store.sections.value[0]?.internal_id).toBeDefined();
            expect(store.sections.value[0]?.id).toBe(section.id);
            expect(store.sections.value[0]?.internal_id).not.toBe(section.id);
        });

        it.each([
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
        ])(
            "should store a pending freetext section when the document is empty and user can edit document and configured tracker = %s",
            (tracker: Tracker) => {
                selected_tracker = tracker;

                const store = getEmptyStore();

                store.replaceAll([]);

                expect(store.sections.value).toHaveLength(1);
                expect(isPendingFreetextSection(store.sections.value[0])).toBe(true);
            },
        );

        it("should store nothing when tracker is not defined", () => {
            const store = getEmptyStore();
            store.replaceAll([]);

            expect(store.sections.value).toHaveLength(0);
        });

        it("should store loaded sections when empty and configured tracker but no rights to edit document", () => {
            selected_tracker = TrackerStub.withTitleAndDescription();
            can_user_edit_document = false;

            const store = getEmptyStore();
            store.replaceAll([]);

            expect(store.sections.value).toHaveLength(0);
        });

        it(`should create a pending artifact section
            when loaded sections are empty
            and there is a configured tracker
            and user can edit document`, () => {
            selected_tracker = TrackerStub.withTitleAndDescription();

            const store = getEmptyStore();
            store.replaceAll([]);

            expect(store.sections.value).toHaveLength(1);
            expect(isPendingArtifactSection(store.sections.value[0])).toBe(true);
        });
    });

    describe("updateSection", () => {
        it("should update the artifact section", () => {
            const section = ArtifactSectionFactory.create();
            const section_a = ArtifactSectionFactory.override({
                ...section,
                id: "section-a",
                title: {
                    ...section.title,
                    value: "Section A",
                },
            });
            const section_b = ArtifactSectionFactory.override({
                ...section,
                id: "section-b",
                title: {
                    ...section.title,
                    value: "Section B",
                },
            });

            const store = getStoreWithSections([section_a, section_b]);
            store.updateSection(
                ArtifactSectionFactory.override({
                    ...section_b,
                    title: {
                        ...section_b.title,
                        value: "Updated section B",
                    },
                }),
            );

            const section_0: SectionBasedOnArtifact = store.sections
                .value[0] as SectionBasedOnArtifact;
            const section_1: SectionBasedOnArtifact = store.sections
                .value[1] as SectionBasedOnArtifact;

            expect(store.sections.value).toHaveLength(2);
            expect(section_0.title.value).toBe("Section A");
            expect(section_1.title.value).toBe("Updated section B");
        });

        it("should update the freetext section", () => {
            const section = FreetextSectionFactory.create();
            const section_a = FreetextSectionFactory.override({
                ...section,
                id: "section-a",
                title: "Section A",
            });
            const section_b = FreetextSectionFactory.override({
                ...section,
                id: "section-b",
                title: "Section B",
            });

            const store = getStoreWithSections([section_a, section_b]);
            store.updateSection(
                FreetextSectionFactory.override({
                    ...section_b,
                    title: "Updated section B",
                }),
            );

            const section_0: FreetextSection = store.sections.value[0] as FreetextSection;
            const section_1: FreetextSection = store.sections.value[1] as FreetextSection;

            expect(store.sections.value).toHaveLength(2);
            expect(section_0.title).toBe("Section A");
            expect(section_1.title).toBe("Updated section B");
        });
    });

    describe("removeSection", () => {
        it.each([
            [null],
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
            [TrackerStub.withTitleAndDescription()],
        ])(
            "should remove the section when it is found and tracker is %s",
            async (tracker: Tracker | null) => {
                selected_tracker = tracker;

                const section1 = ArtifactSectionFactory.create();
                const section2 = PendingArtifactSectionFactory.create();
                const section3 = ArtifactSectionFactory.create();
                const section4 = PendingArtifactSectionFactory.create();

                vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

                const store = getStoreWithSections([section1, section2, section3, section4]);

                store.removeSection(section2, tracker);
                store.removeSection(section3, null);
                await flushPromises();

                expect(store.sections.value).not.toBeUndefined();
                expect(store.sections.value).toHaveLength(2);
                expect(store.sections.value[0].id).toStrictEqual(section1.id);
                expect(store.sections.value[1].id).toStrictEqual(section4.id);
            },
        );

        it.each([
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
        ])(
            "should remove the last section and add automatically a new pending freetext section when tracker is %s",
            async (tracker: Tracker) => {
                const section = ArtifactSectionFactory.create();
                const store = getStoreWithSections([section]);

                vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

                store.removeSection(section, tracker);
                await flushPromises();

                const pending = store.sections.value[0];
                if (pending === undefined) {
                    throw Error("Should get a section");
                }

                expect(store.sections.value).toHaveLength(1);
                expect(isPendingFreetextSection(pending)).toBe(true);
            },
        );

        it("should remove the last section and add automatically a new pending artifact section when tracker has title and description", async () => {
            const section = ArtifactSectionFactory.create();
            const store = getStoreWithSections([section]);

            vi.spyOn(rest, "deleteSection").mockReturnValue(okAsync(new Response()));

            store.removeSection(section, TrackerStub.withTitleAndDescription());
            await flushPromises();

            expect(store.sections.value).toHaveLength(1);
            const pending = store.sections.value[0];
            if (pending === undefined) {
                throw Error("Should get a section");
            }
            expect(isPendingArtifactSection(pending)).toBe(true);
        });

        it("should do nothing when there is no sections", async () => {
            const store = getEmptyStore();
            store.removeSection(ArtifactSectionFactory.create(), null);
            await flushPromises();

            expect(store.sections.value).toHaveLength(0);
        });

        it("should do nothing when section cannot be found", async () => {
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = ArtifactSectionFactory.create();
            const section4 = PendingArtifactSectionFactory.create();
            const store = getStoreWithSections([section1, section2, section3, section4]);

            store.removeSection(ArtifactSectionFactory.create(), null);
            await flushPromises();

            expect(store.sections.value).toHaveLength(4);
            expect(store.sections.value[0].id).toStrictEqual(section1.id);
            expect(store.sections.value[1].id).toStrictEqual(section2.id);
            expect(store.sections.value[2].id).toStrictEqual(section3.id);
            expect(store.sections.value[3].id).toStrictEqual(section4.id);
        });
    });

    describe("insertSection", () => {
        const section1 = ArtifactSectionFactory.create();
        const section2 = PendingArtifactSectionFactory.create();
        const new_section = PendingArtifactSectionFactory.create();

        it("should insert the section at the beginning", () => {
            const store = getStoreWithSections([section1, section2]);

            store.insertSection(new_section, { before: section1.id });

            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value[0].id).toStrictEqual(new_section.id);
            expect(store.sections.value[1].id).toStrictEqual(section1.id);
            expect(store.sections.value[2].id).toStrictEqual(section2.id);
        });

        it("should insert the section before the second one", () => {
            const store = getStoreWithSections([section1, section2]);

            store.insertSection(new_section, { before: section2.id });

            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value[0].id).toStrictEqual(section1.id);
            expect(store.sections.value[1].id).toStrictEqual(new_section.id);
            expect(store.sections.value[2].id).toStrictEqual(section2.id);
        });

        it("should insert the section at the end", () => {
            const store = getStoreWithSections([section1, section2]);

            store.insertSection(new_section, AT_THE_END);

            expect(store.sections.value).toHaveLength(3);
            expect(store.sections.value[0].id).toStrictEqual(section1.id);
            expect(store.sections.value[1].id).toStrictEqual(section2.id);
            expect(store.sections.value[2].id).toStrictEqual(new_section.id);
        });
    });

    describe("insertPendingSectionForEmptyDocument", () => {
        it.each([
            [TrackerStub.withoutTitleAndDescription()],
            [TrackerStub.withTitle()],
            [TrackerStub.withDescription()],
        ])("should insert a pending freetext section if tracker is %s", (tracker: Tracker) => {
            selected_tracker = tracker;

            const store = getEmptyStore();
            store.insertPendingSectionForEmptyDocument(tracker);

            expect(store.sections.value).toHaveLength(1);
            expect(isPendingFreetextSection(store.sections.value[0])).toBe(true);
        });

        it("should insert a pending artifact section when sections is empty", () => {
            const store = getEmptyStore();

            store.insertPendingSectionForEmptyDocument(TrackerStub.withTitleAndDescription());

            expect(store.sections.value).toHaveLength(1);
            const section = store.sections.value[0];
            if (section === undefined) {
                throw Error("Should get a section");
            }
            expect(isPendingArtifactSection(section));
        });

        it("should do nothing when not empty", () => {
            const section = ArtifactSectionFactory.create();
            const store = getStoreWithSections([section]);

            store.insertPendingSectionForEmptyDocument(TrackerStub.withTitleAndDescription());

            expect(store.sections.value).toHaveLength(1);
            expect(store.sections.value[0].id).toStrictEqual(section.id);
        });

        it("should do nothing when tracker is not defined", () => {
            const store = getEmptyStore();
            store.insertPendingSectionForEmptyDocument(null);

            expect(store.sections.value).toHaveLength(0);
        });
    });

    describe("getSectionPositionForSave", () => {
        describe("scenario that should not happen (how can we have a section to get position, but no sections at all in the store?)", () => {
            it("should return at the end if section is not found", () => {
                const store = getEmptyStore();

                expect(
                    store.getSectionPositionForSave(PendingArtifactSectionFactory.create()),
                ).toBeNull();
            });
        });

        it("should return the position that could be used for save", () => {
            const section0 = ArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = ArtifactSectionFactory.create();
            const store = getStoreWithSections([section0, section1, section2]);

            expect(store.getSectionPositionForSave(section0)).toStrictEqual({
                before: section1.id,
            });
            expect(store.getSectionPositionForSave(section1)).toStrictEqual({
                before: section2.id,
            });
            expect(store.getSectionPositionForSave(section2)).toBeNull();
        });

        it("should return the position by excluding pending artifact section because we cannot position a section with a non-existing-yet section", () => {
            const section0 = PendingArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = PendingArtifactSectionFactory.create();
            const section4 = ArtifactSectionFactory.create();
            const section5 = PendingArtifactSectionFactory.create();

            const store = getStoreWithSections([
                section0,
                section1,
                section2,
                section3,
                section4,
                section5,
            ]);

            expect(store.getSectionPositionForSave(section0)).toStrictEqual({
                before: section1.id,
            });
            expect(store.getSectionPositionForSave(section1)).toStrictEqual({
                before: section4.id,
            });
            expect(store.getSectionPositionForSave(section2)).toStrictEqual({
                before: section4.id,
            });
            expect(store.getSectionPositionForSave(section3)).toStrictEqual({
                before: section4.id,
            });
            expect(store.getSectionPositionForSave(section4)).toBeNull();
            expect(store.getSectionPositionForSave(section5)).toBeNull();
        });
    });

    describe("replacePendingSection", () => {
        it("should do nothing if the pending sections cannot be found", () => {
            const section = PendingArtifactSectionFactory.create();
            const store = getStoreWithSections([section]);

            store.replacePendingSection(
                PendingArtifactSectionFactory.create(),
                ArtifactSectionFactory.create(),
            );

            expect(store.sections.value).toHaveLength(1);
            expect(store.sections.value[0].id).toStrictEqual(section.id);
        });

        it("should replace an artifact section", () => {
            const section0 = PendingArtifactSectionFactory.create();
            const section1 = ArtifactSectionFactory.create();
            const section2 = PendingArtifactSectionFactory.create();
            const section3 = PendingArtifactSectionFactory.create();
            const newone = ArtifactSectionFactory.create();

            const store = getStoreWithSections([section0, section1, section2, section3]);

            store.replacePendingSection(section2, newone);

            expect(store.sections.value).toHaveLength(4);
            expect(store.sections.value[0].id).toStrictEqual(section0.id);
            expect(store.sections.value[1].id).toStrictEqual(section1.id);
            expect(store.sections.value[2].id).toStrictEqual(newone.id);
            expect(store.sections.value[3].id).toStrictEqual(section3.id);
        });

        it("should replace a freetext section", () => {
            const section0 = FreetextSectionFactory.create();
            const section1 = FreetextSectionFactory.pending();
            const section2 = FreetextSectionFactory.create();
            const section3 = FreetextSectionFactory.create();
            const newone = FreetextSectionFactory.create();

            const store = getStoreWithSections([section0, section1, section2, section3]);

            if (!isPendingFreetextSection(section1)) {
                throw new Error("Expected a pending freetext section");
            }

            store.replacePendingSection(section1, newone);

            expect(store.sections.value).toHaveLength(4);
            expect(store.sections.value[0].id).toStrictEqual(section0.id);
            expect(store.sections.value[1].id).toStrictEqual(newone.id);
            expect(store.sections.value[2].id).toStrictEqual(section2.id);
            expect(store.sections.value[3].id).toStrictEqual(section3.id);
        });
    });
});
