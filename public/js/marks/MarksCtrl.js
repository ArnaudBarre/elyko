angular.module('elyko')
    .controller('MarksCtrl', ['$scope', 'semesterPromise', '$state', 'evaluationFactory', 'uvFactory',
        function ($scope, semesterPromise, $state, evaluationFactory, uvFactory) {
            $scope.semester = semesterPromise;
            $scope.state = $state;
            $scope.viewEvaluation = function (evaluation) {
                evaluationFactory.studentMark = evaluation.mark;
                $state.go('evaluation', {id: evaluation.id});
            };
            $scope.viewUv = function (uv) {
                uvFactory.studentGrade = uv.grade;
                $state.go('uv', {id: uv.id});
            };
        }
    ]);