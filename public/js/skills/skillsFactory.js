/**
 * Created by tim on 18/05/16.
 */
angular.module('elyko')
    .factory('skillsFactory', ['$http', 'SERVER_PATH', function ($http, SERVER_PATH) {
        return {
            get: function (semester_id) {
                return $http.get(SERVER_PATH + 'skills/' + semester_id).then(function (response) {
                    return response.data;
                })
            }
        }
    }]);