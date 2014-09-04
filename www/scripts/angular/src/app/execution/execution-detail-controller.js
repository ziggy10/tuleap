angular
    .module('execution')
    .controller('ExecutionDetailCtrl', ExecutionDetailCtrl);

ExecutionDetailCtrl.$inject = ['$scope', '$state', '$sce', 'executions', 'ExecutionService', 'SharedPropertiesService'];

function ExecutionDetailCtrl($scope, $state, $sce, executions, ExecutionService, SharedPropertiesService) {
    var execution_id = +$state.params.execid;

    $scope.execution = _.find(_.flatten(executions, 'executions'), function (execution) {
        return execution.id === execution_id;
    });

    $scope.pass           = pass;
    $scope.fail           = fail;
    $scope.block          = block;
    $scope.sanitizeHtml   = sanitizeHtml;
    $scope.getStatusLabel = getStatusLabel;

    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function pass(execution) {
        setNewStatus(execution, "passed");
    }

    function fail(execution) {
        setNewStatus(execution, "failed");
    }

    function block(execution) {
        setNewStatus(execution, "blocked");
    }

    function setNewStatus(execution, new_status) {
        var previous_status = execution.status;

        execution.status = new_status;
        ExecutionService.putExecution(execution).then(function () {
            execution.previous_result.status       = previous_status;
            execution.previous_result.submitted_on = new Date();
            execution.previous_result.submitted_by = SharedPropertiesService.getCurrentUser();
        });
    }

    function getStatusLabel(status) {
        var labels = {
            passed: 'Passed',
            failed: 'Failed',
            blocked: 'Blocked',
            notrun: 'Not Run'
        };

        return labels[status];
    }
}