<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard;

use AgileDashboardRouterBuilder;
use Feedback;
use HTTPRequest;
use Tuleap\AgileDashboard\Kanban\KanbanURL;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithThemeSelection;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class AgileDashboardLegacyController implements DispatchableWithRequest, DispatchableWithThemeSelection
{
    /**
     * @var AgileDashboardRouterBuilder
     */
    private $router_builder;

    public function __construct(AgileDashboardRouterBuilder $router_builder)
    {
        $this->router_builder = $router_builder;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $request->getProject();

        if ($project->isError() || $project->isDeleted()) {
            $layout->addFeedback(Feedback::ERROR, _('This project is deleted'));
            $layout->redirect('/');
        }

        if (KanbanURL::isKanbanURL($request)) {
            $layout->addCssAsset(
                new CssAssetWithoutVariantDeclinaisons(
                    new IncludeAssets(
                        __DIR__ . '/../../scripts/kanban/frontend-assets',
                        '/assets/agiledashboard/kanban'
                    ),
                    'kanban-style'
                )
            );
        } elseif (self::isPlanningV2URL($request)) {
            $layout->addCssAsset(
                new CssAssetWithoutVariantDeclinaisons(
                    new IncludeAssets(
                        __DIR__ . '/../../scripts/planning-v2/frontend-assets',
                        '/assets/agiledashboard/planning-v2'
                    ),
                    'planning-style'
                )
            );
        }

        $router = $this->router_builder->build($request);

        try {
            $router->route($request);
        } catch (\Tuleap\AgileDashboard\Planning\NotFoundException $exception) {
            throw new NotFoundException('', $exception);
        }
    }

    public function isInABurningParrotPage(HTTPRequest $request, array $variables): bool
    {
        return KanbanURL::isKanbanURL($request)
            || $this->isInOverviewTab($request)
            || $this->isPlanningV2URL($request)
            || $this->isScrumAdminURL($request);
    }

    public static function isInOverviewTab(HTTPRequest $request): bool
    {
        return $request->get('action') === DetailsPaneInfo::ACTION
            && $request->get('pane') === DetailsPaneInfo::IDENTIFIER;
    }

    public static function isPlanningV2URL(HTTPRequest $request): bool
    {
        $pane_info_identifier = new \AgileDashboard_PaneInfoIdentifier();

        return $pane_info_identifier->isPaneAPlanningV2($request->get('pane'));
    }

    public static function isScrumAdminURL(HTTPRequest $request): bool
    {
        return $request->get('action') === 'admin'
            && $request->get('pane') !== 'kanban'
            && $request->get('pane') !== 'charts';
    }
}
