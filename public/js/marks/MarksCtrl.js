angular.module('elyko')
    .controller('MarksCtrl', ['$scope', 'semesterPromise', '$state', 'evaluationFactory',
        function ($scope, semesterPromise, $state, evaluationFactory) {
            $scope.semester = semesterPromise;
            $scope.state = $state;
            $scope.viewEvaluation = function (evaluation) {
                evaluationFactory.studentMark = evaluation.mark;
                $state.go('evaluation', {id: evaluation.id});
            };
        }
    ]);