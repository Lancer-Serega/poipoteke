(function () {
    'use strict';

    var Suggestion = function (value) {
        var _ = this,
            suggestion = angular.isDefined(value) ? value : {};

        _.getId = function () {
            return angular.isDefined(suggestion.id) ? suggestion.id : 0;
        };

        _.getName = function () {
            return angular.isDefined(suggestion.name) ? suggestion.name : '';
        };

        _.getNameSystem = function() {
            return angular.isDefined(suggestion.nameSystem) ? suggestion.nameSystem : '';
        };

        _.getNameHighlighted = function () {
            return angular.isDefined(suggestion.nameHighlighted) ? suggestion.nameHighlighted : _.getName();
        };
    };

    var AutocompleteDirective = function ($timeout) {
        return {
            restrict: 'AC',
            scope: {
                provider: '=',
                providerCallback: '=',
                selectedSuggestion: '=',
                validator: '=',
                transpose: '@',
                name: '@',
                placeholder: '@',
                hintSearch: '@',
                hintSearchProcess: '@',
                hintNoResults: '@'
            },
            template: '<input type="text" class="form__input" placeholder="{{ placeholder }}" autocomplete="off" '
            + 'name="{{name}}" '
            + 'ng-class="{\'form__input--invalid\': !isValid()}"'
            + 'ng-model="searchString" '
            + 'ng-model-options="{getterSetter: true}"'
            + 'ng-keydown="onKeydown($event)" '
            + 'ng-focus="onFocus()" '
            + 'ng-blur="onBlur()" '
            + 'ng-change="filterSuggestions()"> '
            + '<div class="suggestions-wrapper" ng-show="isVisibleSuggestions()">'
            + '<div class="suggestions-list">'
            + '<div class="suggestions-list__hint suggestions-list__hint--search" '
            + 'ng-bind-html="hintSearch" '
            + 'ng-show="hintSearch"></div>'
            + '<div class="suggestions-list__hint suggestions-list__hint--search-process" '
            + 'ng-bind-html="hintSearchProcess" '
            + 'ng-show="isSearchSuggestions() && hintSearchProcess"></div>'
            + '<div class="suggestions-list__hint suggestions-list__hint--no-results" '
            + 'ng-bind-html="hintNoResults" '
            + 'ng-show="isNoSuggestions() && hintNoResults"></div>'
            + '<p class="suggestions-list__item" '
            + 'ng-repeat="suggestion in getSuggestions() track by $index" '
            + 'ng-click="setSuggestion($index)" '
            + 'ng-class="{\'suggestions-list__item--selected\': isSelectedSuggestion($index), '
            + '\'suggestions-list__item--touched\': isTouchedSuggestion($index)}" '
            + 'ng-bind-html="suggestion.getNameHighlighted()"></p>'
            + '</div>'
            + '</div>',
            link: function (scope, element) {
                var KEY_UP = 38,
                    KEY_DOWN = 40,
                    KEY_ENTER = 13,
                    KEY_TAB = 9,
                    KEY_ESC = 27,
                    KEY_SHIFT = 16,
                    KEY_CTRL = 17,
                    KEY_ALT = 18;

                var TIMEOUT_TYPING = 500,
                    TIMEOUT_BLUR = 200,
                    TIMEOUT_SEARCH = 200;

                var MATCH_PREFIX = '<b class="suggestion-list__highlighted">',
                    MATCH_SUFFIX = '</b>';

                var _timerTyping = null,
                    _timerBlur = null,
                    _timerSearch = null,
                    _suggestions = [],
                    _suggestionsFiltered = [],
                    _touchedSuggestion = {},
                    _searchString = '',
                    _isFocused = false,
                    _isValidState = true,
                    _isVisibleSuggestions = false,
                    _isSearchProcess = false;

                element.removeAttr('data-provider');
                element.removeAttr('data-provider-callback');
                element.removeAttr('data-selected-suggestion');
                element.removeAttr('data-validator');
                element.removeAttr('data-name');
                element.removeAttr('data-placeholder');
                element.removeAttr('data-hint-search');
                element.removeAttr('data-hint-search-process');
                element.removeAttr('data-hint-no-results');

                scope.$watchCollection('provider', function (newSuggestions, oldSuggestions) {
                    if (newSuggestions !== oldSuggestions) {
                        initSuggestions(newSuggestions);
                    }
                });

                scope.$on('$destroy', function () {
                    $timeout.cancel(_timerTyping);
                    $timeout.cancel(_timerBlur);
                    $timeout.cancel(_timerSearch);
                });

                scope.searchString = searchString;

                scope.isSearchSuggestions = function () {
                    return isSearchProcess();
                };

                scope.isNoSuggestions = function () {
                    return !getFilteredSuggestions().length && searchStringReal() && !isSearchProcess();
                };

                scope.getSuggestions = function () {
                    return getFilteredSuggestions();
                };

                scope.setSuggestion = function (index, autoSelection) {
                    var selectedSuggestion = getFilteredSuggestions()[index];

                    setSelectedSuggestion(selectedSuggestion);
                    searchString(selectedSuggestion.getName());

                    if (!autoSelection) {
                        scope.onBlur(true);
                        scope.filterSuggestions(true);
                    }

                    validate();
                };

                scope.isSelectedSuggestion = function (index) {
                    return angular.isDefined(getFilteredSuggestions()[index])
                        && getFilteredSuggestions()[index].getId() === getSelectedSuggestion().getId();
                };

                scope.isTouchedSuggestion = function (index) {
                    return angular.isDefined(getFilteredSuggestions()[index])
                        && getFilteredSuggestions()[index].getId() === getTouchedSuggestion().getId();
                };

                scope.onFocus = function () {
                    showSuggestions();
                    captureFocus();
                };

                scope.onBlur = function (force) {
                    $timeout.cancel(_timerBlur);

                    if (angular.isDefined(force)) {
                        releaseFocus();
                        hideSuggestions();
                        validate();
                    } else {
                        _timerBlur = $timeout(function () {
                            releaseFocus();
                            hideSuggestions();
                            validate();
                        }, TIMEOUT_BLUR);
                    }
                };

                scope.onKeydown = function (evt) {
                    var keyCode = evt.which ? evt.which : evt.keyCode,
                        currentIndex = -1,
                        direction = keyCode === KEY_DOWN ? 1 : (keyCode === KEY_UP ? -1 : 0),
                        suggestionSelected = keyCode === KEY_ENTER,
                        filteredSuggestions = getFilteredSuggestions();

                    if ([KEY_TAB, KEY_ESC].indexOf(keyCode) >= 0) {
                        scope.onBlur(true);

                        return false;
                    }

                    showSuggestions();

                    if ([KEY_SHIFT, KEY_CTRL, KEY_ALT].indexOf(keyCode) >= 0) {
                        return false;
                    }

                    if (suggestionSelected) {
                        evt.preventDefault();
                    }

                    if (!filteredSuggestions.length || !direction && !suggestionSelected) {
                        if (getTouchedSuggestion() || true) {
                            resetTouchedSuggestion();
                        }

                        return false;
                    }

                    angular.forEach(filteredSuggestions, function (value, index) {
                        if (value.getId() === getTouchedSuggestion().getId()) {
                            currentIndex = index;
                        }
                    });

                    if (suggestionSelected && currentIndex >= 0 && currentIndex <= filteredSuggestions.length) {
                        scope.setSuggestion(currentIndex);

                        evt.preventDefault();

                        return false;
                    }

                    if (currentIndex < 0) {
                        currentIndex = direction > 0 ? 0 : filteredSuggestions.length - 1;
                    } else {
                        currentIndex +=
                            direction < 0 && currentIndex > 0
                                ? direction
                                : (direction > 0 && currentIndex < filteredSuggestions.length - 1 ? direction : 0)
                    }

                    var touchedSuggestion = getFilteredSuggestions()[currentIndex];

                    setTouchedSuggestion(touchedSuggestion);
                    searchString(touchedSuggestion.getName());
                };

                scope.filterSuggestions = function (manualSelection) {
                    startSearchProcess();

                    if (providerIsRemote() && angular.isUndefined(manualSelection)) {
                        resetSelectedSuggestion();
                        remoteProviderCallback();
                    } else {
                        filterMatchedSuggestions();
                    }
                };

                scope.isValid = function () {
                    return isValid();
                };

                scope.isVisibleSuggestions = function () {
                    return isVisibleSuggestions();
                };

                function searchString(value) {
                    return arguments.length
                        ? (_searchString = value.replace(/,/g, ', ').replace(/  +/g, ' ').trim())
                        : _searchString;
                }

                function searchStringReal() {
                    var selectedSuggestion = getSelectedSuggestion();

                    return selectedSuggestion.getId()
                        ? (getTranspose() ? selectedSuggestion.getNameSystem() : selectedSuggestion.getName())
                        : searchString();
                }

                function filterMatchedSuggestions() {
                    var fullMatchedSuggestionIndex = -1,
                        filterBy = searchStringReal().toLowerCase(),
                        searchStringParts = filterBy.trim().split(','),
                        searchStringPartsFiltered = [],
                        regExp = null;

                    resetFilteredSuggestions([]);

                    angular.forEach(searchStringParts, function(splitCommaValue) {
                        splitCommaValue = splitCommaValue.split(' ');

                        if (splitCommaValue.length) {
                            angular.forEach(splitCommaValue, function(splitSpaceValue) {
                                if (splitSpaceValue && searchStringPartsFiltered.indexOf(splitSpaceValue) < 0) {
                                    searchStringPartsFiltered.push(splitSpaceValue);
                                }
                            });
                        }
                    });

                    regExp = new RegExp('(' + searchStringPartsFiltered.join('|') + ')', 'ig');

                    if (filterBy) {
                        angular.forEach(getAllSuggestions(), function (suggestion) {
                            var suggestionName = getTranspose() ? suggestion.getNameSystem() : suggestion.getName(),
                                matches = suggestionName.match(regExp),
                                nameHighlighted = suggestionName,
                                replacedMatches = [];

                            if (filterBy === suggestionName.toLowerCase()) {
                                fullMatchedSuggestionIndex = getFilteredSuggestions().length;

                                addFilteredSuggestion({
                                    id: suggestion.getId(),
                                    name: suggestion.getName(),
                                    nameSystem: suggestion.getNameSystem(),
                                    nameHighlighted: nameHighlighted
                                });
                            } else if (matches) {
                                matches = matches.sort(function(matchA, matchB) {
                                    return matchB.length - matchA.length;
                                });

                                angular.forEach(matches, function(match) {
                                    if (replacedMatches.indexOf(match) < 0) {
                                        nameHighlighted = nameHighlighted
                                            .replace(new RegExp(match, 'g'), wrapMatch(match));

                                        replacedMatches.push(match);
                                    }
                                });

                                addFilteredSuggestion({
                                    id: suggestion.getId(),
                                    name: suggestion.getName(),
                                    nameSystem: suggestion.getNameSystem(),
                                    nameHighlighted: nameHighlighted
                                });
                            }
                        });
                    } else {
                        resetFilteredSuggestions();
                    }

                    if (fullMatchedSuggestionIndex >= 0) {
                        scope.setSuggestion(fullMatchedSuggestionIndex, true);
                    } else {
                        resetSelectedSuggestion();
                    }

                    stopSearchProcess();
                }

                function initSuggestions(suggestionsList) {
                    resetAllSuggestions();
                    resetSelectedSuggestion();

                    angular.forEach(suggestionsList, function (suggestion) {
                        addSuggestion(suggestion);
                    });

                    filterMatchedSuggestions();
                }

                function resetAllSuggestions() {
                    _suggestions = [];
                }

                function resetFilteredSuggestions(value) {
                    _suggestionsFiltered = angular.isDefined(value) ? value : getAllSuggestions();
                }

                function addSuggestion(value) {
                    var name = value.name,
                        nameSystem = value.nameSystem;

                    if (getTranspose()) {
                        value.name = nameSystem;
                        value.nameSystem = name;
                    }

                    _suggestions.push(new Suggestion(value));
                }

                function addFilteredSuggestion(value) {
                    _suggestionsFiltered.push(new Suggestion(value));
                }

                function getFilteredSuggestions() {
                    return _suggestionsFiltered;
                }

                function getAllSuggestions() {
                    return _suggestions;
                }

                function resetSelectedSuggestion() {
                    setSelectedSuggestion(new Suggestion());
                }

                function setSelectedSuggestion(value) {
                    scope.selectedSuggestion(value);
                }

                function getSelectedSuggestion() {
                    if (angular.isUndefined(scope.selectedSuggestion().getId)) {
                        resetSelectedSuggestion();
                    }

                    return scope.selectedSuggestion();
                }

                function resetTouchedSuggestion() {
                    setTouchedSuggestion(new Suggestion());
                }

                function setTouchedSuggestion(value) {
                    _touchedSuggestion = value;
                }

                function getTouchedSuggestion() {
                    if (angular.isUndefined(_touchedSuggestion.getId)) {
                        resetTouchedSuggestion();
                    }

                    return _touchedSuggestion;
                }

                function providerIsRemote() {
                    return angular.isFunction(scope.providerCallback);
                }

                function remoteProviderCallback() {
                    $timeout.cancel(_timerTyping);

                    _timerTyping = $timeout(function () {
                        scope.providerCallback(searchStringReal());
                    }, TIMEOUT_TYPING);
                }

                function validate() {
                    _isValidState = angular.isFunction(scope.validator) ? scope.validator() : true;
                }

                function isValid() {
                    return _isValidState;
                }

                function isVisibleSuggestions() {
                    return _isVisibleSuggestions;
                }

                function hideSuggestions() {
                    _isVisibleSuggestions = false;
                }

                function showSuggestions() {
                    _isVisibleSuggestions = true;
                }

                function wrapMatch(match) {
                    return MATCH_PREFIX + match + MATCH_SUFFIX;
                }

                function startSearchProcess() {
                    _isSearchProcess = true;
                }

                function stopSearchProcess() {
                    $timeout.cancel(_timerSearch);

                    _timerSearch = $timeout(function () {
                        _isSearchProcess = false;
                    }, TIMEOUT_SEARCH);
                }

                function isSearchProcess() {
                    return _isSearchProcess;
                }

                function captureFocus() {
                    _isFocused = true;
                }

                function releaseFocus() {
                    _isFocused = false;
                }

                function isFocus() {
                    return _isFocused;
                }

                function getTranspose() {
                    return angular.isDefined(scope.transpose) && scope.transpose;
                }
            }
        };
    };

    AutocompleteDirective.$inject = ['$timeout'];

    angular.module('ui')
        .directive('uiAutocomplete', AutocompleteDirective);
})();
