angular.module('elyko')
    .factory('marksFactory', ['$http', 'SERVER_PATH', function ($http, SERVER_PATH) {
        return {
            get: function (semester_id) {
                return $http.get(SERVER_PATH + 'marks/' + semester_id).then(function (response) {
                    return response.data;
                });
            },
            getLast: function () {
                return $http.get(SERVER_PATH + 'marks').then(function (response) {
                    return response.data;
                });
            }
        }
    }]);