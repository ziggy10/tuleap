export default ExecutionRestService;

ExecutionRestService.$inject = [
    '$http',
    'Restangular',
    'SharedPropertiesService'
];

function ExecutionRestService(
    $http,
    Restangular,
    SharedPropertiesService
) {
    Object.assign(Restangular.configuration.defaultHeaders,
        { 'X-Client-UUID': SharedPropertiesService.getUUID() }
    );

    const self    = this;
    const baseurl = '/api/v1';
    const rest    = Restangular.withConfig(setRestangularConfig);

    Object.assign(self, {
        getRemoteExecutions,
        postTestExecution,
        putTestExecution,
        changePresenceOnTestExecution,
        leaveTestExecution,
        getArtifactById,
        linkIssue,
        linkIssueWithoutComment,
        getLinkedArtifacts
    });

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getRemoteExecutions(campaign_id, limit, offset) {
        return rest.one('testmanagement_campaigns', campaign_id)
            .all('testmanagement_executions')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                var result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function postTestExecution(tracker_id, definition_id, status) {
        return rest.all('testmanagement_executions')
            .post({
                tracker      : {id: tracker_id},
                definition_id: definition_id,
                status       : status
            })
            .then(function (response) {
                return response.data;
            });
    }

    function putTestExecution(execution_id, new_status, time, results) {
        return rest
            .one('testmanagement_executions', execution_id)
            .put({
                status: new_status,
                time: time,
                results: results
            })
            .then(function (response) {
                return response.data;
            });
    }

    function changePresenceOnTestExecution(execution_id, old_execution_id) {
        return rest
            .one('testmanagement_executions', execution_id)
            .all('presences')
            .patch({
                uuid: SharedPropertiesService.getUUID(),
                remove_from: old_execution_id
            });
    }

    function leaveTestExecution(execution_id) {
        return changePresenceOnTestExecution(execution_id, execution_id);
    }

    function getArtifactById(artifact_id) {
        return rest
            .one('artifacts', artifact_id)
            .get()
            .then(function(response) {
                return response.data;
            });
    }

    function linkIssue(issue_id, test_execution) {
        var comment = {
            body: '<p>' + test_execution.previous_result.result + '</p>'
                + ' <em>' + test_execution.definition.summary + '</em><br/>'
                + '<blockquote>' + test_execution.definition.description + '</blockquote>',
            format: 'html'
        };

        return linkExecutionToIssue(issue_id, test_execution, comment);
    }

    function linkIssueWithoutComment(issue_id, test_execution) {
        var comment = {
            body: '',
            format: 'text'
        };

        return linkExecutionToIssue(issue_id, test_execution, comment);
    }

    function linkExecutionToIssue(issue_id, test_execution, comment) {
        return rest
            .one('testmanagement_executions', test_execution.id)
            .all('issues')
            .patch({
                issue_id: issue_id,
                comment: comment
            })
            .catch(function (response) {
                return Promise.reject(response.data.error);
            });
    }

    function getLinkedArtifacts(test_execution, limit, offset) {
        const { id: execution_id } = test_execution;
        return $http.get(`/api/v1/artifacts/${execution_id}/linked_artifacts`, {
            params: {
                direction: 'forward',
                nature: '',
                limit,
                offset
            }
        }).then((response) => {
            return {
                collection: response.data.collection,
                total: Number.parseInt(response.headers('X-PAGINATION-SIZE'), 10)
            };
        });
    }
}

