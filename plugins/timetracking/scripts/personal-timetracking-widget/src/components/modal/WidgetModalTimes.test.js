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

import { shallowMount } from "@vue/test-utils";
import WidgetModalTimes from "./WidgetModalTimes.vue";
import { createLocalVueForTests } from "../../helpers/local-vue.js";

describe("Given a personal timetracking widget modal", () => {
    let current_artifact;

    async function getWidgetModalTimesInstance() {
        const component_options = {
            localVue: await createLocalVueForTests(),
            propsData: {
                artifact: current_artifact,
            },
        };
        return shallowMount(WidgetModalTimes, component_options);
    }

    it("When current artifact is not empty, then modal content should be displayed", async () => {
        current_artifact = { artifact: "artifact" };
        const wrapper = await getWidgetModalTimesInstance();
        expect(wrapper.find("[data-test=modal-content]").exists()).toBeTruthy();
    });

    it("When current artifact is empty, then modal content should not be displayed", async () => {
        current_artifact = null;
        const wrapper = await getWidgetModalTimesInstance();
        expect(wrapper.find("[data-test=modal-content]").exists()).toBeFalsy();
    });
});
