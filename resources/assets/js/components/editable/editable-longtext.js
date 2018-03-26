(function() {
    'use strict';

    var directive = function() {
        return {
            restrict: 'A',
            replace: true,
            scope: {label: '@', required: '@', placeholder: '@', model: '='},
            controller: 'EditableController',
            controllerAs: 'Text',
            template:
                '<div class="editable-field">'
                + '<label data-ng-show="Text.isEditMode()" data-ng-class="{\'is-invalid-label\': required && !model.length}">'
                + '{{label}}<span class="is-required" data-ng-if="required">&nbsp;*</span>&nbsp;<i class="fi-check" data-ng-click="Text.toggleEditMode()" data-ng-if="required && model.length || !required"></i>'
                + '<textarea data-ng-model="model" placeholder="{{placeholder}}" data-ng-class="{\'is-invalid-input\': required && !model.length}"></textarea>'
                + '</label>'
                + '<p data-ng-show="!Text.isEditMode()">'
                + '<b>{{label}}:&nbsp;</b>{{model}}'
                + '&nbsp;<i class="fi-pencil" data-ng-click="Text.toggleEditMode()"></i>'
                + '</p>'
                + '</div>'
        };
    };

    angular
        .module('editable.longtext', [])
        .directive('editableLongtext', directive);
})();