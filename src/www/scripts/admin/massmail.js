/**
 * Copyright (c) Enalean SAS - 2016. All rights reserved
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

(function () {
    var warning_element     = document.getElementById('massmail-warning'),
        destination_element = document.getElementById('massmail-destination'),
        submit_button       = document.getElementById('massmail-submit'),
        preview_button      = document.getElementById('massmail-preview-destination-submit'),
        preview_feedback    = document.getElementById('massmail-preview-feedback'),
        confirm_element     = document.getElementById('massmail-modal-warning'),
        confirm_button      = document.getElementById('massmail-confirm-sending'),
        confirm_modal       = tlp.modal(confirm_element),
        form                = preview_button.form,
        preview_timeout;

    if (! warning_element || ! destination_element) {
        return;
    }

    changeWarningTextAccordinglyToDestination();
    destination_element.addEventListener('change', changeWarningTextAccordinglyToDestination);
    preview_button.addEventListener('click', sendAPreview);
    form.addEventListener('submit', openConfirmationModal);
    confirm_button.addEventListener('click', confirmationSubmitsTheForm);
    initHTMLEditor();
    initSelect2();

    function changeWarningTextAccordinglyToDestination() {
        warning_element.innerHTML = destination_element[destination_element.selectedIndex].dataset.warning;

        submit_button.disabled = (destination_element[destination_element.selectedIndex].dataset.nbUsers < 1);
    }

    function openConfirmationModal(event) {
        event.preventDefault();
        confirm_modal.show();
    }

    function confirmationSubmitsTheForm() {
        form.submit();
    }

    function initHTMLEditor() {
        CKEDITOR.replace('mail_message', {
            toolbar: [
                ['Bold', 'Italic', 'Underline'],
                ['NumberedList', 'BulletedList', '-', 'Blockquote', 'Format'],
                ['Link', 'Unlink', 'Anchor', 'Image'],
                ['Source']
            ]
        });
    }

    function initSelect2() {
        var preview = document.getElementById('massmail-preview-destination');
        if (! preview) {
            return;
        }

        tuleap.autocomplete_users_for_select2(preview);
    }

    function sendAPreview() {
        var data = new FormData(form);

        clearFeedback();

        data.append('destination', 'preview');

        var req = new XMLHttpRequest();
        req.open('POST', form.action);
        req.onload = previewResponseHandler;
        req.send(data);
    }

    function previewResponseHandler() {
        try {
            response = JSON.parse(this.responseText);
        } catch (e) {
            // ignore SyntaxError
        }

        if (! response || ! response.success) {
            preview_feedback.classList.add('tlp-alert-danger');
            preview_feedback.innerHTML = response.message || 'Something is wrong with your request';
        } else {
            preview_feedback.classList.add('tlp-alert-success');
            preview_feedback.innerHTML = response.message;
        }

        preview_timeout = window.setTimeout(clearFeedback, 5000);
    }

    function clearFeedback() {
        preview_feedback.innerHTML = '';
        preview_feedback.classList.remove('tlp-alert-success');
        preview_feedback.classList.remove('tlp-alert-danger');

        window.clearTimeout(preview_timeout);
        preview_timeout = undefined;
    }
})();