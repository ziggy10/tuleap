/*
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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
import type { MarkSpec, DOMOutputSpec } from "prosemirror-model";
import { Schema } from "prosemirror-model";

const strongDOM: DOMOutputSpec = ["strong", 0];

export const nodes = {};

const strong: MarkSpec = {
    parseDOM: [
        { tag: "strong" },
        {
            tag: "b",
            getAttrs: (node: HTMLElement) => node.style.fontWeight !== "normal" && null,
        },
        { style: "font-weight=400", clearMark: (m) => m.type.name === "strong" },
        {
            style: "font-weight",
            getAttrs: (value: string) => /^(bold(er)?|[5-9]\d{2,})$/.test(value) && null,
        },
    ],
    toDOM() {
        return strongDOM;
    },
};
export const marks = {
    strong,
};

export const schema = new Schema({ nodes, marks });
