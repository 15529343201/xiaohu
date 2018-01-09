;(function()
{
  'use strict';
  
  angular.module('xiaohu',[
    'ui.router',

  ])
    .config(function($interpolateProvider,
                     $stateProvider,
                     $urlRouterProvider)
    {
       $interpolateProvider.startSymbol('[:');
       $interpolateProvider.endSymbol(':]');

       $urlRouterProvider.otherwise('/home');

       $stateProvider
         .state('home',{
           url:'/home',
           templateUrl:'home.tpl'
         })
         .state('signup',{
           url:'/signup',
           templateUrl:'signup.tpl'
         })
         .state('login',{
           url:'/login',
           templateUrl:'login.tpl'
         })
    })  
})();
