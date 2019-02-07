<?php
/**
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline;

use HTTPRequest;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ServiceController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    /**
     * @var TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \baselinePlugin
     */
    private $plugin;

    public function __construct(\ProjectManager $project_manager, TemplateRenderer $template_renderer, \baselinePlugin $plugin)
    {
        $this->project_manager   = $project_manager;
        $this->template_renderer = $template_renderer;
        $this->plugin            = $plugin;
    }


    private function includeJavascriptFiles(BaseLayout $layout)
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/baseline/scripts',
            '/assets/baseline/scripts'
        );

        $layout->includeFooterJavascriptFile($include_assets->getFileURL('baseline.js'));
    }

    private function includeCssFiles(BaseLayout $layout)
    {
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../src/www/assets/baseline/BurningParrot',
                    '/assets/baseline/BurningParrot'
                ),
                'baseline'
            )
        );
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\baselinePlugin::NAME);

        $project = $this->getProjectByName($variables['project_name']);

        if (! $this->plugin->isAllowed($project->getID())) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-baseline', 'Baseline service is disabled for this project'));
            $layout->redirect('/projects/'.$variables['project_name']);
        }

        $this->includeCssFiles($layout);
        $this->includeJavascriptFiles($layout);

        $layout->header(
            [
                'title'        => dgettext('tuleap-baseline', "Baselines"),
                'group'        => $project->getID(),
                'toptab'       => \baselinePlugin::SERVICE_SHORTNAME,
            ]
        );
        $this->template_renderer->renderToPage('project-service-index', []);
        $layout->footer(["without_content" => true]);
    }

    /**
     * Return the project that corresponds to current URI
     *
     * This part of controller is needed when you implement a new route without providing a $group_id.
     * It's the preferred way to deal with those kind of URLs over Event::GET_PROJECTID_FROM_URL
     *
     * @param \HTTPRequest $request
     * @param array        $variables
     *
     * @return Project
     * @throws NotFoundException
     */
    public function getProject(\HTTPRequest $request, array $variables)
    {
        return $this->getProjectByName($variables['project_name']);
    }

    /**
     * @param string $name
     * @return Project
     * @throws NotFoundException
     */
    private function getProjectByName(string $name): \Project
    {
        $project = $this->project_manager->getProjectByUnixName($name);
        if (! $project) {
            throw new NotFoundException();
        }
        return $project;
    }
}
