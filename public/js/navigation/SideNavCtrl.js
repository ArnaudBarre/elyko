angular.module('elyko')
    .controller('SideNavCtrl', ['$scope', 'ssSideNav', 'studentFactory', '$rootScope',
        function ($scope, ssSideNav, studentFactory, $rootScope) {
            $rootScope.student = studentFactory.get().then(function (response) {
                $rootScope.student = response;
                initializeSideNav(response.semesters);
            });

            $scope.menu = ssSideNav;

            function initializeSideNav(semesters) {
                for (var i =  semesters.length -1; i >= 0; i--) {
                    var semester = semesters[i];

                    ssSideNav.sections[0].pages.push({
                        id: semester.id,
                        name: semester.name,
                        state: "marks({semester_id: '" + semester.id + "'})"
                    });

                    ssSideNav.sections[1].pages.push({
                        id: semester.id,
                        name: semester.name,
                        state: "skills({semester_id: '" + semester.id + "'})"
                    })
                }
            }

        }]);