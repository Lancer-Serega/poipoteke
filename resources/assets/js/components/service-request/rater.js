(function () {
    var Rater = function (rater) {
        var _ = this,
            objectTypeId = 0;

        rater = angular.isDefined(rater) ? rater : {};
        rater.id = angular.isDefined(rater.id) ? rater.id : '';
        rater.name = angular.isDefined(rater.name) ? rater.name : '';
        rater.pricePerMeter = angular.isDefined(rater.pricePerMeter) ? rater.pricePerMeter : 0;
        rater.pricesReport = angular.isDefined(rater.pricesReport) ? rater.pricesReport : [];
        rater.contactsList = angular.isDefined(rater.contactsList) ? rater.contactsList : [];

        _.getId = function () {
            return rater.id;
        };

        _.getName = function () {
            return rater.name;
        };

        _.getReportCost = function () {
            var distances = [],
                reports = rater.pricesReport.filter(function (report) {
                    return report.objectTypeId === objectTypeId;
                });

            angular.forEach(rater.contactsList, function (contact) {
                distances.push(contact.distance);
            });

            var minDistance = distances.length ? distances.min() : 0;
            var reportCost = reports.length ? reports.pop().price : 0;

            return Math.ceil(reportCost + rater.pricePerMeter * minDistance);
        };

        _.getReportDate = function () {
            var reports = rater.pricesReport.filter(function (report) {
                return report.objectTypeId === objectTypeId;
            });

            return reports.length ? reports.pop().date : '';
        };

        _.getReportDateFormat = function () {
            var reports = rater.pricesReport.filter(function (report) {
                return report.objectTypeId === objectTypeId;
            });

            return reports.length ? reports.pop().dateFormat : '';
        };

        _.getPricesReport = function () {
            return rater.pricesReport;
        };

        _.getContactsList = function() {
            return rater.contactsList;
        };

        _.setObjectTypeId = function (value) {
            objectTypeId = value;
        };
    };

    var RatersService = function ($http, $q, Map) {
        var _ = this,
            _ratersRaw = [],
            _ratersFiltered = [],
            _contacts = [],
            _states = {available: 'AVAILABLE', processing: 'PROCESSING'},
            _state = 'AVAILABLE',
            _bankId = 0,
            _objectTypeId = 0,
            _objectCoordinates = [],
            _contactsProcessed = 0;

        _.load = function (params) {
            if (!angular.equals(getBankId(), params.bankId)) {
                resetRatersInfo();
                setStateProcessing();

                setBankId(params.bankId);
                setObjectTypeId(params.objectTypeId);
                setObjectCoordinates(params.objectCoordinates);

                $http.get('/api/get-raters', {params: {bankId: getBankId()}})
                    .then(function (response) {
                        setStateAvailable();
                        setContacts(response.data.contacts);
                        setRawRaters(response.data.raters);
                        updateRaters(true);
                    }, function () {
                        setStateAvailable();
                    });
            } else {
                if (!angular.equals(getObjectCoordinates(), params.objectCoordinates)) {
                    setObjectCoordinates(params.objectCoordinates);
                    updateRaters(true);
                }

                if (!angular.equals(getObjectTypeId(), params.objectTypeId)) {
                    setObjectTypeId(params.objectTypeId);
                    updateRaters(true);
                }
            }
        };

        _.getRaters = getFilteredRaters;

        _.getBestByCost = function () {
            var raters = getFilteredRaters(),
                costs = [];

            angular.forEach(raters, function (rater) {
                costs.push(rater.getReportCost());
            });

            var minCost = costs.min();

            if (angular.isDefined(raters[costs.indexOf(minCost)])) {
                return [raters[costs.indexOf(minCost)]];
            }
        };

        _.getBestByDate = function () {
            var raters = getFilteredRaters(),
                dates = [];

            angular.forEach(raters, function (rater) {
                dates.push(rater.getReportDate());
            });

            var minDate = dates.min();

            if (angular.isDefined(raters[dates.indexOf(minDate)])) {
                return [raters[dates.indexOf(minDate)]];
            }
        };

        _.getState = getState;

        function resetRatersInfo() {
            setRawRaters([]);
            setFilteredRaters([]);
            setContacts([]);
        }

        function setRawRaters(raters) {
            var ratersList = [];

            angular.forEach(raters, function (rater) {
                rater.contactsList = getContacts().filter(function (contact) {
                    return contact.raterId === rater.id;
                });

                ratersList.push(new Rater(rater));
            });

            _ratersRaw = ratersList;
        }

        function getRawRaters() {
            return _ratersRaw;
        }

        function setFilteredRaters(value) {
            _ratersFiltered = value;
        }

        function getFilteredRaters() {
            return _ratersFiltered;
        }

        function filterRaters() {
            var objectTypeId = getObjectTypeId(),
                raters = [];

            setFilteredRaters([]);

            angular.forEach(getRawRaters(), function (rater) {
                var pricesReport = rater.getPricesReport().filter(function (report) {
                    return objectTypeId === report.objectTypeId;
                });

                if (pricesReport.length) {
                    rater.setObjectTypeId(objectTypeId);
                    raters.push(rater);
                }
            });

            raters.sort(function(raterA, raterB) {
                return raterA.getReportCost() - raterB.getReportCost();
            });

            setFilteredRaters(raters);
        }

        function setContacts(value) {
            _contacts = value;
        }

        function getContacts() {
            return _contacts;
        }

        function updateRaters(updateContacts) {
            setFilteredRaters([]);

            if (angular.isDefined(updateContacts)) {
                setStateProcessing();
                setContactsProcessed(0);

                angular.forEach(getContacts(), function (contact, index) {
                    getRouteLength([JSON.parse(contact.coordinates), getObjectCoordinates()], index)
                        .then(function (response) {
                            updateRaterContactProcess(response[0], response[1]);
                        }, function(response) {
                            updateRaterContactProcess(response[0], response[1]);
                        });
                });
            } else {
                filterRaters();
            }
        }

        function updateRaterContactProcess(contactIndex, contactDistance) {
            updateContactDistance(contactIndex, contactDistance);
            setContactsProcessed(getContactsProcessed() + 1);

            if (getContactsProcessed() === getContacts().length) {
                filterRaters();
                setStateAvailable();
            }
        }

        function getRouteLength(points, index) {
            return $q(function (resolve, reject) {
                Map.getRouteLength(points)
                    .then(function (response) {
                        resolve([index, response.getLength()])
                    }, function () {
                        reject([index, 0])
                    });
            });
        }

        function updateContactDistance(index, value) {
            _contacts[index]['distance'] = value;
        }

        function setBankId(value) {
            _bankId = value;
        }

        function getBankId() {
            return _bankId;
        }

        function setObjectTypeId(value) {
            _objectTypeId = value;
        }

        function getObjectTypeId() {
            return _objectTypeId;
        }

        function setObjectCoordinates(value) {
            var coordinates = [];

            coordinates.push((angular.isDefined(value[0]) ? value[0] : 0));
            coordinates.push((angular.isDefined(value[1]) ? value[1] : 0));

            _objectCoordinates = coordinates;
        }

        function getObjectCoordinates() {
            if (angular.isUndefined(_objectCoordinates[0]) || angular.isUndefined(_objectCoordinates[1])) {
                setObjectCoordinates();
            }

            return _objectCoordinates;
        }

        function setContactsProcessed(value) {
            _contactsProcessed = value;
        }

        function getContactsProcessed() {
            return _contactsProcessed;
        }

        function setStateProcessing() {
            _state = _states.processing;
        }

        function setStateAvailable() {
            _state = _states.available;
        }

        function getState() {
            return _state;
        }
    };

    RatersService.$inject = ['$http', '$q', 'MapFactory'];

    angular.module('serviceRequest')
        .service('RatersService', RatersService);
})();
