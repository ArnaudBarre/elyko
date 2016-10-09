/**
 * Created by sebastian on 16/05/16.
 */
angular.module('elyko')
    .factory('studentFactory', ['$http', 'SERVER_PATH',
        function ($http, SERVER_PATH) {
            return {
                get: function () {
                    return $http.get(SERVER_PATH + 'student').then(function (response) {
                        return response.data;
                    });
                }
            }
        }]);