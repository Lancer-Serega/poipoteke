(function() {
    'use strict';

    var directive = function() {
        return {
            replace: true,
            scope: {label: '@', model: '='},
            template: '<label>{{label}}<input type="text" data-ng-model="model"></label>'
        }
    };

    angular
        .module('editable.timerange', [])
        .directive('editableTimerange', directive);
})();