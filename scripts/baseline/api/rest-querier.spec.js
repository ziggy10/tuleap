/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

import { rewire$get, rewire$post } from "tlp-fetch";
import { mockFetchSuccess } from "tlp-mocks";
import {
    getOpenMilestones,
    getBaselines,
    getComparisons,
    getBaselineArtifactsByIds,
    createBaseline
} from "./rest-querier";
import { create, createList } from "../support/factories";

describe("Rest queries:", () => {
    let result;

    describe("getOpenMilestones()", () => {
        let get;

        const simplified_milestone = createList("milestone", 1);

        beforeEach(async () => {
            get = jasmine.createSpy("get");
            mockFetchSuccess(get, { return_json: simplified_milestone });
            rewire$get(get);
            result = await getOpenMilestones(1);
        });

        it("calls projects API to get opened milestones", () =>
            expect(get).toHaveBeenCalledWith('/api/projects/1/milestones?query={"status":"open"}'));

        it("returns open milestones", () => expect(result).toEqual(simplified_milestone));
    });

    describe("getBaselines()", () => {
        let get;

        const baseline = create("baseline");

        beforeEach(async () => {
            get = jasmine.createSpy("get");
            mockFetchSuccess(get, { return_json: { baselines: [baseline] } });
            rewire$get(get);
            result = await getBaselines(1);
        });

        it("calls projects API to get baselines", () =>
            expect(get).toHaveBeenCalledWith("/api/projects/1/baselines?limit=1000&offset=0"));

        it("returns baselines", () => expect(result).toEqual([baseline]));
    });

    describe("getComparisons()", () => {
        let get;

        const comparison = create("comparison");

        beforeEach(async () => {
            get = jasmine.createSpy("get");
            mockFetchSuccess(get, { return_json: { comparisons: [comparison] } });
            rewire$get(get);
            result = await getComparisons(1);
        });

        it("calls projects API to get comparisons", () =>
            expect(get).toHaveBeenCalledWith(
                "/api/projects/1/baselines_comparisons?limit=1000&offset=0"
            ));

        it("returns comparisons", () => expect(result).toEqual([comparison]));
    });

    describe("createBaseline()", () => {
        let post;

        const baseline = create("baseline");
        const headers = {
            "content-type": "application/json"
        };
        const body = JSON.stringify({
            name: "My first baseline",
            artifact_id: 3
        });

        beforeEach(async () => {
            post = jasmine.createSpy("post");
            mockFetchSuccess(post, { return_json: baseline });
            rewire$post(post);

            result = await createBaseline("My first baseline", {
                id: 3,
                label: "milestone Label",
                snapshot_date: "2019-04-29"
            });
        });

        it("calls baselines API to create baseline", () =>
            expect(post).toHaveBeenCalledWith("/api/baselines/", { headers, body }));

        it("returns created baseline", () => expect(result).toEqual(baseline));
    });

    describe("getBaselineArtifactsByIds()", () => {
        let get;

        beforeEach(async () => {
            get = jasmine.createSpy("get");
            mockFetchSuccess(get, { return_json: {} });
            rewire$get(get);

            result = await getBaselineArtifactsByIds(1, [1, 2, 3, 4]);
        });

        it("calls baselines API to get baseline artifacts by ids", () =>
            expect(get).toHaveBeenCalledWith(
                "/api/baselines/1/artifacts?query=%7B%22ids%22%3A%5B1%2C2%2C3%2C4%5D%7D"
            ));
    });
});
