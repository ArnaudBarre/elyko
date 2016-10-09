/**
 * Created by tim on 18/05/16.
 */
angular.module('elyko')
    .controller('SkillsCtrl', ['$scope', '$stateParams', '$rootScope', 'skillsPromise',
        function ($scope, $stateParams, $rootScope, skillsPromise) {
            $scope.semester = $rootScope.student.semesters.filter(function (semester) {
                return semester.id == $stateParams.semester_id;
            })[0].name;
            $scope.labels = get(skillsPromise, 'labels');
            $scope.data = [get(skillsPromise, 'data')];

            function get(skillsPromise, thingToGet) {
                var tab = [];

                for (var skill in skillsPromise) {
                    if (skillsPromise.hasOwnProperty(skill)) {
                        if (thingToGet == 'labels') tab.push(skill + " (" + skillsPromise[skill][1] + ")");
                        else if (thingToGet == 'data') tab.push(skillsPromise[skill][0]);
                    }
                }

                return tab;
            }
        }
    ]);