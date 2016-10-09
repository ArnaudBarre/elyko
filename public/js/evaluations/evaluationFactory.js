/**
 * Created by sebastian on 16/05/16.
 */
angular.module('elyko')
    .factory('evaluationFactory', ['$http', 'SERVER_PATH', function ($http, SERVER_PATH) {
        return {
            studentMark: null,
            get: function (id) {
                return $http.get(SERVER_PATH + 'evaluation/' + id).then(function (response) {
                    return response.data;
                })
            }
        }
    }]);