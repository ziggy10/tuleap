<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook\Actions;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tracker;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Webhook\Webhook;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookRetriever;
use Valid_HTTPURI;

class WebhookEditController implements DispatchableWithRequest
{

    /**
     * @var WebhookRetriever
     */
    private $retriever;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(WebhookRetriever $retriever, TrackerFactory $tracker_factory, WebhookDao $dao)
    {
        $this->retriever       = $retriever;
        $this->tracker_factory = $tracker_factory;
        $this->dao             = $dao;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $webhook_id = $request->get('webhook_id');

        if (! $webhook_id) {
            $layout->redirect('/');
        }

        $webhook = $this->retriever->getWebhookById($webhook_id);
        if (! $webhook) {
            throw new NotFoundException();
        }

        $tracker = $this->tracker_factory->getTrackerById($webhook->getTrackerId());
        if (! $tracker) {
            throw new NotFoundException();
        }

        $redirect_url = $this->getAdminWebhooksURL($tracker);
        $webhook_url  = $this->getValidURL($request, $layout, $redirect_url);

        $user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($user)) {
            throw new ForbiddenException();
        }

        $csrf = $this->getCSRFSynchronizerToken($tracker);
        $csrf->check();

        $this->dao->edit($webhook_id, $webhook_url);

        $layout->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-tracker', 'Webhook sucessfully updated')
        );

        $layout->redirect($redirect_url);
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken(Tracker $tracker)
    {
        return new CSRFSynchronizerToken($this->getAdminWebhooksURL($tracker));
    }

    private function getAdminWebhooksURL(Tracker $tracker)
    {
        return '/plugins/tracker/?' . http_build_query(
            [
                "func"    => "admin-webhooks",
                "tracker" => $tracker->getId()
            ]
        );
    }

    private function getValidURL(HTTPRequest $request, BaseLayout $layout, $redirect_url)
    {
        $valid_url = new Valid_HTTPURI('webhook_url');
        $valid_url->required();

        if (! $request->valid($valid_url)) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The submitted URL is not valid.')
            );
            $layout->redirect($redirect_url);
        }

        return $request->get('webhook_url');
    }
}
