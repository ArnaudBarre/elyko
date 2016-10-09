angular.module('elyko')
    .controller('ToolbarCtrl', ['$scope', '$mdSidenav', '$state',
        function ($scope, $mdSidenav, $state) {

            $scope.showSearchBar = false;

            $scope.searchButtonIsVisible = function () {
                return $state.is('marks');
            };

            $scope.searchBarIsVisible = function () {
                return $scope.showSearchBar && $state.is('marks');
            };

            $scope.showMenu = function () {
                $mdSidenav('left').toggle();
            };
        }]);