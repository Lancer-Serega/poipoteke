(function() {
    'use strict';

    var controller = function(MessageBagService) {
        var _ = this;

        _.getMessagesList = function() {
            return MessageBagService.getMessagesList();
        };

        _.deleteMessage = function(index) {
            MessageBagService.deleteMessage(index);
        };
    };

    controller.$inject = ['MessageBagService'];

    var Message = function(message) {
        var _ = this,
            id = angular.isDefined(message.id) ? message.id : 'noid',
            template = angular.isDefined(message.template) ? message.template : '',
            className = angular.isDefined(message.className) ? message.className : '',
            responsible = angular.isDefined(message.responseText),
            responseText = angular.isDefined(message.responseText) ? message.responseText : '',
            responseStatus = false;

        _.getId = function() {
            return id;
        };

        _.getTemplate = function() {
            return template;
        };

        _.getClassName = function() {
            return className;
        };

        _.isResponsible = function() {
            return responsible;
        };

        _.getResponseText = function() {
            return responseText;
        };

        _.getResponseStatus = function() {
            return responseStatus;
        };

        _.toggleResponseStatus = function() {
            responseStatus = !responseStatus;
        };
    };

    var service = function() {
        var _ = this,
            messages = [];

        _.getMessagesList = function() {
            return messages;
        };

        _.addMessage = function(message) {
            messages.unshift(new Message(message));
        };

        _.deleteMessage = function(index) {
            messages.splice(index, 1);
        };

        _.getMessageById = function(messageId) {
            var messageContainer = messages[getMessageIndexById(messageId)];

            return messageContainer;
        };

        _.deleteMessageById = function(messageId) {
            _.deleteMessage(getMessageIndexById(messageId));
        };

        function getMessageIndexById(messageId) {
            var index = {};

            angular.forEach(messages, function(message, messageKey) {
                if (message.getId() === messageId) {
                    index = messageKey;
                }
            });

            return index;
        }
    };

    var directive = function() {
        return {
            restrict: 'A',
            replace: true,
            scope: {},
            controller: controller,
            controllerAs: 'Messages',
            template:
                '<div class="message-bag">'
                + '<div class="row">'
                + '<div class="small-12 columns">'
                + '<div class="callout {{message.getClassName()}}" data-ng-repeat="message in Messages.getMessagesList() track by $index">'
                    + '<p data-ng-bind-html="message.getTemplate()"></p>'
                    + '<button class="close-button" aria-label="Закрыть" data-ng-if="!message.isResponsible()"><span aria-hidden="true" data-ng-click="Messages.deleteMessage($index)">×</span></button>'
                    + '<button class="button small warning" aria-label="{{message.getResponseText()}}" data-ng-if="message.isResponsible()" data-ng-click="message.toggleResponseStatus()">{{message.getResponseText()}}</button>'
                + '</div>'
                + '</div>'
                + '</div>'
                + '</div>'
        };
    };

    angular
        .module('messageBag', [])
        .service('MessageBagService', service)
        .directive('messageBag', directive);
})();
