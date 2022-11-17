/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import DropDownQuickLook from "./DropDownQuickLook.vue";
import type { Folder, Item, ItemFile } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ConfigurationState } from "../../../store/configuration";

describe("DropDownQuickLook", () => {
    function createWrapper(
        item: Item,
        forbid_writers_to_update: boolean
    ): Wrapper<DropDownQuickLook> {
        return shallowMount(DropDownQuickLook, {
            localVue,
            propsData: { item },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            forbid_writers_to_update,
                        } as ConfigurationState,
                    },
                }),
            },
        });
    }

    it(`Given item is not a folder and user can write
        When we display the menu
        Then the drop down does not display New folder/document entries`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            } as ItemFile,
            false
        );

        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeTruthy();
    });

    it(`Given item is not a folder and user can read
        When we display the menu
        Then does not display lock informations`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
            } as ItemFile,
            false
        );

        expect(wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-dropdown-menu-unlock-item]").exists()).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is writer
        When we display the menu
        Then it does not display update properties entry`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                can_user_manage: false,
            } as ItemFile,
            true
        );

        expect(
            wrapper.find("[data-test=document-dropdown-menu-update-properties]").exists()
        ).toBeFalsy();
    });

    it(`Given writers are not allowed to update properties
        And user is manager
        When we display the menu
        Then it displays update properties entry`, () => {
        const wrapper = createWrapper(
            {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
                can_user_manage: true,
            } as ItemFile,
            true
        );

        expect(
            wrapper.find("[data-test=document-dropdown-menu-update-properties]").exists()
        ).toBeTruthy();
    });

    describe("Given item is a folder", () => {
        it(`When the dropdown is open
            Then user should not have the "create new version" option`, () => {
            const wrapper = createWrapper(
                {
                    id: 1,
                    title: "my folder",
                    type: "folder",
                    user_can_write: true,
                } as Folder,
                false
            );

            expect(
                wrapper.find("[data-test=document-quicklook-action-button-new-version]").exists()
            ).toBeFalsy();
            expect(
                wrapper.find("[data-test=document-dropdown-menu-update-properties]").exists()
            ).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()
            ).toBeFalsy();
        });

        it(`When user cannot write and the menu is displayed
            Then the user should not be able to create folder/documents`, () => {
            const wrapper = createWrapper(
                {
                    id: 1,
                    title: "my folder",
                    type: "folder",
                    user_can_write: false,
                } as Folder,
                false
            );

            expect(
                wrapper.find("[data-test=document-dropdown-menu-update-properties]").exists()
            ).toBeFalsy();
            expect(
                wrapper.find("[data-test=document-dropdown-menu-lock-item]").exists()
            ).toBeFalsy();
        });
    });
});
