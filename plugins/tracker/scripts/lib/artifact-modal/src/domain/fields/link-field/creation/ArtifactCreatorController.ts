/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import type { DispatchEvents } from "../../../DispatchEvents";
import { WillDisableSubmit } from "../../../submit/WillDisableSubmit";
import { WillEnableSubmit } from "../../../submit/WillEnableSubmit";
import type { RetrieveProjects } from "./RetrieveProjects";
import type { Project } from "../../../Project";
import type { Tracker } from "../../../Tracker";
import { ProjectsRetrievalFault } from "./ProjectsRetrievalFault";
import type { RetrieveProjectTrackers } from "./RetrieveProjectTrackers";
import { ProjectTrackersRetrievalFault } from "./ProjectTrackersRetrievalFault";
import { ProjectIdentifier } from "../../../ProjectIdentifier";
import type { CurrentProjectIdentifier } from "../../../CurrentProjectIdentifier";

type OnFaultHandler = (fault: Fault) => void;

export type ArtifactCreatorController = {
    registerFaultListener(handler: OnFaultHandler): void;
    getProjects(): PromiseLike<readonly Project[]>;
    selectProjectAndGetItsTrackers(project_id: ProjectIdentifier): PromiseLike<readonly Tracker[]>;
    disableSubmit(reason: string): void;
    enableSubmit(): void;
    getSelectedProject(): ProjectIdentifier;
    getUserLocale(): string;
};

export const ArtifactCreatorController = (
    event_dispatcher: DispatchEvents,
    projects_retriever: RetrieveProjects,
    project_trackers_retriever: RetrieveProjectTrackers,
    current_project_identifier: CurrentProjectIdentifier,
    user_locale: string
): ArtifactCreatorController => {
    let _handler: Option<OnFaultHandler> = Option.nothing();
    let selected_project: ProjectIdentifier = ProjectIdentifier.fromCurrentProject(
        current_project_identifier
    );

    return {
        selectProjectAndGetItsTrackers(project_id): PromiseLike<readonly Tracker[]> {
            selected_project = project_id;
            return project_trackers_retriever.getTrackersByProject(selected_project).match(
                (trackers) => trackers,
                (fault) => {
                    _handler.apply((handler) => handler(ProjectTrackersRetrievalFault(fault)));
                    return [];
                }
            );
        },

        registerFaultListener: (handler): void => {
            _handler = Option.fromValue(handler);
        },

        getProjects: () =>
            projects_retriever.getProjects().match(
                (projects) => projects,
                (fault) => {
                    _handler.apply((handler) => handler(ProjectsRetrievalFault(fault)));
                    return [];
                }
            ),

        disableSubmit(reason): void {
            event_dispatcher.dispatch(WillDisableSubmit(reason));
        },

        enableSubmit(): void {
            event_dispatcher.dispatch(WillEnableSubmit());
        },

        getSelectedProject: () => selected_project,

        getUserLocale: () => user_locale,
    };
};
