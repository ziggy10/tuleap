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

import type { PdfTemplate } from "@tuleap/print-as-pdf";
import { printAsPdf } from "@tuleap/print-as-pdf";
import type CodeMirror from "codemirror";

export function initiatePrintPreview(
    style: CodeMirror.EditorFromTextArea,
    title_page_content_editor: CodeMirror.EditorFromTextArea,
    header_content_editor: CodeMirror.EditorFromTextArea,
    footer_content_editor: CodeMirror.EditorFromTextArea,
): void {
    const button = document.getElementById("pdftemplate-print-preview");
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const fake_document = document.getElementById("pdftemplate-print-preview-fake-document");
    if (!(fake_document instanceof HTMLElement)) {
        throw Error("Cannot found fake document");
    }

    const [label, description] = ["input-label", "input-description"].map((id) =>
        document.getElementById(id),
    );
    if (!(label instanceof HTMLInputElement)) {
        throw Error("Cannot found label input");
    }
    if (!(description instanceof HTMLTextAreaElement)) {
        throw Error("Cannot found description textarea");
    }

    button.addEventListener("click", () => {
        const template: PdfTemplate = {
            id: "",
            label: label.value,
            description: description.value,
            style:
                style.getValue() +
                `
                .pdftemplate-preview-variable {
                    background: rgba(213, 216, 220, 0.5);
                    color: rgb(88, 88, 88);
                    border-radius: 3px;
                    padding: 5px 8px;
                }
            `,
            title_page_content: title_page_content_editor.getValue(),
            header_content: header_content_editor.getValue(),
            footer_content: footer_content_editor.getValue(),
        };

        printAsPdf(fake_document, template, {
            // eslint-disable-next-line no-template-curly-in-string
            DOCUMENT_TITLE: "<span class='pdftemplate-preview-variable'>${DOCUMENT_TITLE}</span>",
        }).mapErr(
            // eslint-disable-next-line no-console
            (fault) => console.error(fault.toString()),
        );
    });
}
