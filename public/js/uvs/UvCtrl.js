/**
 * Created by tim on 20/05/16.
 */
angular.module('elyko')
    .controller('UvCtrl', ['$scope', 'UvPromise',
        function ($scope, UvPromise) {
            $scope.name = UvPromise.name;
            $scope.credits = UvPromise.credits;
            $scope.average = UvPromise.average;
            $scope.labels = getLabels();
            $scope.data = getData();

            function getLabels() {
                var labels = [];

                for (var label in UvPromise.grades) {
                    if (UvPromise.grades[label] !== 0 && UvPromise.grades.hasOwnProperty(label))
                        labels.push(label);
                }

                return labels;
            }

            function getData() {
                var data = [];

                for (var label in UvPromise.grades) {
                    if (UvPromise.grades[label] !== 0 && UvPromise.grades.hasOwnProperty(label))
                        data.push(UvPromise.grades[label]);
                }

                return [data];
            }

        }]);
