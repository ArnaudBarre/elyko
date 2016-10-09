/**
 * Created by sebastian on 16/05/16.
 */
angular.module('elyko')
    .controller('EvaluationCtrl', ['$scope', 'evaluationPromise', 'evaluationFactory',
        function ($scope, evaluationPromise, evaluationFactory) {
            $scope.name = evaluationPromise.name;
            $scope.coefficient = evaluationPromise.coefficient;
            $scope.average = evaluationPromise.average;
            $scope.mark = evaluationFactory.studentMark;
            $scope.labels = getLabels();
            $scope.data = getData();

            function getLabels() {
                var labels = [];

                for (var label in evaluationPromise.marks) {
                    if (evaluationPromise.marks[label] != 0 && evaluationPromise.marks.hasOwnProperty(label))
                        labels.push(label);
                }

                return labels;
            }

            function getData() {
                var data = [];

                for (var label in evaluationPromise.marks) {
                    if (evaluationPromise.marks[label] != 0 && evaluationPromise.marks.hasOwnProperty(label))
                        data.push(evaluationPromise.marks[label]);
                }

                return [data];
            }

        }]);