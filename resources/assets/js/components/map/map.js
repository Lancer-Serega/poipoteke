(function () {
    var PropertyIterator = function (propertyObject) {
        var _ = this;

        _.get = function (property) {
            return propertyObject.hasOwnProperty(property) ? propertyObject[property] : '';
        };

        _.next = function (property) {
            propertyObject = _.get(property);

            return _;
        };
    };

    var MapFactory = function ($q) {
        var GEOCODE_RESULTS_LIMIT = 1,
            SUGGESTIONS_RESULTS_LIMIT = 7;

        var map = {
                geocode: null,
                getSuggestions: null,
                getLocation: null,
                getRouteLength: null
            },
            location = {
                position: '',
                country: '',
                administrativeArea: '',
                subAdministrativeArea: '',
                locality: ''
            };

        ymaps.ready(initMap);

        function initMap() {
            var geolocation = ymaps.geolocation;

            $q(function (resolve) {
                geolocation.get({provider: 'yandex', autoReverseGeocode: true})
                    .then(function (result) {
                        resolve(result);
                    });
            }).then(function (result) {
                var geoObjects = result.geoObjects,
                    properties = new PropertyIterator(geoObjects.get(0).properties.get('metaDataProperty'));

                location.position = geoObjects.position;
                location.country = properties.next('GeocoderMetaData').next('AddressDetails').next('Country')
                    .get('CountryName');
                location.administrativeArea = properties.next('AdministrativeArea').get('AdministrativeAreaName');
                location.subAdministrativeArea = properties.next('SubAdministrativeArea')
                    .get('SubAdministrativeAreaName');
                location.locality = properties.next('Locality').get('LocalityName');
            }, function () {
            });

            map.geocode = function (query) {
                return $q(function (resolve) {
                    ymaps.geocode(query, {results: GEOCODE_RESULTS_LIMIT}).then(function (response) {
                        resolve(response.geoObjects.get(0).geometry.getCoordinates());
                    });
                });
            };

            map.getSuggestions = function (query) {
                return $q(function(resolve, reject) {
                    ymaps.suggest(query, {results: SUGGESTIONS_RESULTS_LIMIT})
                        .then(function(items) {
                            if (items.length) {
                                resolve(items);
                            } else {
                                reject();
                            }
                        }, function() {
                            reject();
                        });
                });
            };

            map.getRouteLength = function (points) {
                return $q(function (resolve) {
                    ymaps.route(points).then(function (response) {
                        resolve(response);
                    });
                });
            };
        }

        return map;
    };

    MapFactory.$inject = ['$q'];

    angular.module('map', [])
        .factory('MapFactory', MapFactory);
})();
