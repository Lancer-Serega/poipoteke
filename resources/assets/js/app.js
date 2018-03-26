(function() {
    'use strict';

    var config = function() {};
    var run = function() {};

    angular
        .module('poipoteke', [
            'ngSanitize',
            'angular-360-no-scope',
            'ngMask',
            'ui',
            'map',
            'editable',
            'serviceRequest'
        ])
        .config(config)
        .run(run);
})();
