(function() {
    'use strict';

    var controller = function() {
        var _ = this,
            editMode = false;

        _.isEditMode = function() {
            return editMode;
        }

        _.toggleEditMode = function() {
            editMode = !editMode;
        }
    };

    angular
        .module('editable', ['editable.text', 'editable.longtext', 'editable.timerange'])
        .controller('EditableController', controller);
})();