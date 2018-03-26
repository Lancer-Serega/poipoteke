(function () {
    'use strict';

    var RequestController = function ($http, $timeout, SearchFilters, Raters) {
        var RATERS_PER_PAGE = 12;

        var _ = this,
            _steps = ['step-filters', 'step-raters', 'step-request'],
            _step = 0,
            _states = {process: 'state-process', available: 'state-available'},
            _bankSelected = {},
            _objectTypeSelected = {},
            _addressSelected = {},
            _raterSelected = {},
            _ratersPage = 0,
            _objectCoordinates = [0, 0],
            _requestId = 0,
            _someval = '',
            _user = {name: '', email: '', phone: ''};

        SearchFilters.load();

        _.getBanks = SearchFilters.getBanks;
        _.getObjectTypes = SearchFilters.getObjectTypes;
        _.getAddresses = SearchFilters.getAddresses;
        _.updateAddresses = SearchFilters.updateAddresses;

        /* search form */
        _.getBankSelected = function (value) {
            return arguments.length ? (_bankSelected = value) : _bankSelected;
        };

        _.getObjectTypeSelected = function (value) {
            return arguments.length ? (_objectTypeSelected = value) : _objectTypeSelected;
        };

        _.getAddressSelected = function (value) {
            return arguments.length ? (_addressSelected = value) : _addressSelected;
        };

        _.isInvalidForm = function () {
            return !_.isValidForm();
        };

        _.isValidForm = function () {
            return _.isValidBank() && _.isValidObjectType() && _.isValidAddress();
        };

        _.isValidBank = function () {
            return angular.isDefined(_.getBankSelected().getId) && _.getBankSelected().getId();
        };

        _.isValidObjectType = function () {
            return angular.isDefined(_.getObjectTypeSelected().getId) && _.getObjectTypeSelected().getId();
        };

        _.isValidAddress = function () {
            return angular.isDefined(_.getAddressSelected().getId) && _.getAddressSelected().getId();
        };

        _.processSearch = function () {
            if (_.isValidForm()) {
                SearchFilters.getAddressCoordinates(_.getAddressSelected().getNameSystem())
                    .then(function (coordinates) {
                        _objectCoordinates = coordinates;

                        Raters.load({
                            bankId: _.getBankSelected().getId(),
                            objectTypeId: _.getObjectTypeSelected().getId(),
                            objectCoordinates: coordinates
                        });

                        nextStep();
                    });
            }
        };

        /* raters */
        _.getRaters = function () {
            return Raters.getRaters().slice(_.getRatersListStartIndex() - 1, _.getRatersListEndIndex());
        };

        _.getBestRaterByCost = Raters.getBestByCost;
        _.getBestRaterByDate = Raters.getBestByDate;

        _.isRatersExists = function () {
            return _.getRaters().length;
        };

        _.getRaterSelected = function (index) {
            if (angular.isDefined(index)) {
                _raterSelected = _.getRaters()[index];

                nextStep();
            } else {
                _raterSelected = {};

                prevStep();
            }
        };

        _.isRaterSelected = function () {
            return angular.isDefined(_raterSelected.getId) && _raterSelected.getId();
        };

        _.showRatersListPagination = function () {
            return RATERS_PER_PAGE < _.getRatersListLength();
        };

        _.nextRatersListPage = function () {
            setRatersListPage(getRatersListPage()
                + (getRatersListPage() * RATERS_PER_PAGE + 1 < _.getRatersListLength() - 1 ? 1 : 0));
        };

        _.prevRatersListPage = function () {
            setRatersListPage(getRatersListPage() - (getRatersListPage() > 0 ? 1 : 0));
        };

        _.getRatersListStartIndex = function () {
            var startIndex = _ratersPage * RATERS_PER_PAGE + 1;

            return startIndex;
        };

        _.getRatersListEndIndex = function () {
            var endIndex = _.getRatersListStartIndex() - 1 + RATERS_PER_PAGE,
                endIndexReal = _.getRatersListLength();

            return endIndex <= endIndexReal ? endIndex : endIndexReal;
        };

        _.getRatersListLength = function () {
            return Raters.getRaters().length;
        };

        /* request */
        _.getUserName = function (value) {
            return arguments.length ? (_user.name = value) : _user.name;
        };

        _.getUserEmail = function (value) {
            return arguments.length ? (_user.email = value) : _user.email;
        };

        _.getUserPhone = function (value) {
            if (arguments.length) {
                value = value.replace(/[^0-9.]/g, '');

                var valueParts = [value.slice(0, 3), value.slice(3, 6), value.slice(6,8), value.slice(8,10)];

                if (valueParts[0].length) {
                    valueParts[0] = '(' + valueParts[0];
                }

                if (valueParts[1].length) {
                    valueParts[1] = ') ' + valueParts[1];
                }

                if (valueParts[2].length) {
                    valueParts[2] = '-' + valueParts[2];
                }

                if (valueParts[3].length) {
                    valueParts[3] = '-' + valueParts[3];
                }

                _user.phone = valueParts.join('');
            }

            return _user.phone;
        };

        _.createRequest = function () {
            var distances= [],
                contactsList = _raterSelected.getContactsList(),
                data = {
                    bankId: _bankSelected.getId(),
                    raterId: _raterSelected.getId(),
                    objectTypeId: _objectTypeSelected.getId(),
                    address: _addressSelected.getName(),
                    coordinates: '[' + _objectCoordinates.toString() + ']',
                    contactId: 0,
                    distance: 0,
                    name: _.getUserName(),
                    email: _.getUserEmail(),
                    phone: _.getUserPhone().replace(/[^0-9.]/g, '')
                };

            angular.forEach(contactsList, function (contact) {
                distances.push(contact.distance);
            });

            data.distance = distances.min();
            data.contactId = contactsList[distances.indexOf(data.distance)].id;

            if (!_requestId) {
                $http.post('/api/create-request', serialize(data), {
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).then(function (response) {
                    _requestId = response.data.requestId;

                    alert('Заявка #' + response.data.requestId + ' создана');
                }, function (response) {
                    alert('Во время создания заявки возникла ошибка. Код ошибки: ' + response.data.errcode + '.');
                });
            }
        };

        /* states */
        _.stateSearchRaters = function () {
            Raters.getState().match(/processing/i)
        };

        _.getStateName = function () {
            return SearchFilters.getState().match(/processing/i) || Raters.getState().match(/processing/i)
                ? _states.process : _states.available;
        };

        _.getStepName = function () {
            return _steps[_step];
        };

        _.prevStep = prevStep;

        /* private */
        function setRatersListPage(value) {
            _ratersPage = value;
        }

        function getRatersListPage() {
            return _ratersPage;
        }

        function nextStep() {
            _step += _step < _steps.length - 1 ? 1 : 0;
        }

        function prevStep() {
            _step -= _step ? 1 : 0;
        }
    };

    RequestController.$inject = ['$http', '$timeout', 'FiltersService', 'RatersService'];

    angular.module('serviceRequest')
        .controller('RequestController', RequestController);
})();
