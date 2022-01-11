/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import { getLinkFieldUnderConstructionPlaceholder } from "../../gettext-catalog";

export type HostElement = LinkField & HTMLElement;

export interface LinkField {
    fieldId: number;
    label: string;
}

export const LinkField = define<LinkField>({
    tag: "tuleap-artifact-modal-link-field-v2",
    fieldId: 0,
    label: "",
    content: (host) => html`
        <div class="tlp-form-element">
            <label for="${"tracker_field_" + host.fieldId}" class="tlp-label">${host.label}</label>
            <input
                id="${"tracker_field_" + host.fieldId}"
                type="text"
                class="tlp-input"
                placeholder="${getLinkFieldUnderConstructionPlaceholder()}"
                disabled
            />
        </div>
    `,
});
