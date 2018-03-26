(function() {
    var FiltersService = function($http, $q, Map) {
        var _ = this,
            _states = {available: 'AVAILABLE', processing: 'PROCESSING'},
            _state = 'AVAILABLE',
            _banks = [],
            _objectTypes = [],
            _addresses = [];

        _.load = function() {
            resetAll();
            setStateProcessing();

            $http.get('/api/get-search-form-data', {})
                .then(function(response) {
                    setStateAvailable();

                    angular.forEach(response.data.banks, function(bank) {
                        _banks.push({id: bank.id, name: bank.name});
                    });

                    angular.forEach(response.data.objectTypes, function(objectType) {
                        _objectTypes.push({id: objectType.id, name: objectType.name});
                    });
                }, function() {
                    setStateAvailable();
                });
        };

        _.getBanks = function() {
            return _banks;
        };

        _.getObjectTypes = function() {
            return _objectTypes;
        };

        _.getAddresses = function() {
            return _addresses;
        };

        _.updateAddresses = function(address) {
            setStateProcessing();
            resetAddresses();

            Map.getSuggestions(address)
                .then(function(items) {
                    setStateAvailable();

                    angular.forEach(items, function(item, index) {
                        _addresses.push({id: index + 1, name: item.displayName, nameSystem: item.value});
                    });
                }, function() {
                    setStateAvailable();
                });
        };

        _.getAddressCoordinates = function(address) {
            setStateProcessing();

            return $q(function (resolve) {
                Map.geocode(address)
                    .then(function(coordinates) {
                        setStateAvailable();

                        resolve(coordinates);
                    }, function() {
                        setStateAvailable();
                    });
            });
        };

        _.getSuggestions = function() {
            return _suggestions;
        };

        _.getState = function() {
            return _state;
        };

        function resetAddresses() {
            _addresses = [{}];
        }

        function resetAll() {
            _banks = [];
            _objectTypes = [];
            _addresses = []
        }

        function setStateProcessing() {
            _state = _states.processing;
        }

        function setStateAvailable() {
            _state = _states.available;
        }
    };

    FiltersService.$inject = ['$http', '$q', 'MapFactory'];

    angular.module('serviceRequest')
        .service('FiltersService', FiltersService);
})();
