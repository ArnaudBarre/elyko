angular.module('elyko', ['ui.router', 'ngMaterial', 'md.data.table', 'chart.js', 'sasrio.angular-material-sidenav'])
    .constant("SERVER_PATH", "index.php/")
    .config(['$stateProvider', '$urlRouterProvider', '$mdThemingProvider', 'ssSideNavSectionsProvider',
        function ($stateProvider, $urlRouterProvider, $mdThemingProvider, ssSideNavSectionsProvider) {
            $stateProvider
                .state('marks', {
                    url: '/marks/{semester_id}',
                    templateUrl: 'js/marks/_marks.html',
                    controller: 'MarksCtrl',
                    resolve: {
                        semesterPromise: ['$stateParams', 'marksFactory', function ($stateParams, marksFactory) {
                            if ($stateParams.semester_id)
                                return marksFactory.get($stateParams.semester_id);
                            else
                                return marksFactory.getLast();

                        }]
                    }
                })
                .state('uv', {
                    url: '/uv/{id}',
                    templateUrl: 'js/uvs/_uv.html',
                    controller: 'UvCtrl',
                    resolve: {
                        UvPromise: ['$stateParams', 'uvFactory', function ($stateParams, uvFactory) {
                            return uvFactory.get($stateParams.id);
                        }]
                    }
                })
                .state('evaluation', {
                    url: '/evaluation/{id}',
                    templateUrl: 'js/evaluations/_evaluation.html',
                    controller: 'EvaluationCtrl',
                    resolve: {
                        evaluationPromise: ['$stateParams', 'evaluationFactory',
                            function ($stateParams, evaluationFactory) {
                                return evaluationFactory.get($stateParams.id);
                            }]
                    }
                })
                .state('skills', {
                    url: '/skills/{semester_id}',
                    templateUrl: 'js/skills/_skills.html',
                    controller: 'SkillsCtrl',
                    resolve: {
                        skillsPromise: ['$stateParams', 'skillsFactory', function ($stateParams, skillsFactory) {
                            return skillsFactory.get($stateParams.semester_id);
                        }]
                    }
                });

            $urlRouterProvider.otherwise(function ($injector) {
                $injector.invoke(['$state', function ($state) {
                    $state.go('marks');
                }]);
            });

            $mdThemingProvider.theme('default')
                .primaryPalette("teal")
                .accentPalette("indigo");

            ssSideNavSectionsProvider.initWithSections([
                {
                    name: 'Mes Notes',
                    type: 'toggle',
                    pages: []
                },
                {
                    name: 'Mes Competences',
                    type: 'toggle',
                    pages: []

                }
            ]);

            ssSideNavSectionsProvider.initWithTheme($mdThemingProvider);
        }
    ]);