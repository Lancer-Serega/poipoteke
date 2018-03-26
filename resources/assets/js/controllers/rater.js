(function() {
    'use strict';

    var controller = function(MessageBagService) {
        var _ = this,
            raterOriginal = {
                title: 'ООО «Оценка недвижимости»',
                email: 'example@mail.com',
                about: 'Действие сериала разворачивается за шесть лет до того, как его главный герой, на то время еще мелкий адвокатишка Сол Гудман знакомится с Уолтером Уайтом по прозвищу Хайзенберг. Сол пытается открыть свою собственную адвокатскую контору в родном городе Альбукерке, что в штате Нью-Мексико.',
                requisites: 'Расчетный счет 30301810000006000001, Кор. счет 30101810400000000225 в Главном управлении Центрального банка Российской Федерации по Центральному федеральному округу г. Москва (ГУ Банка России по ЦФО), БИК 044525225, КПП 775001001, ИНН 7707083893'
            },
            raterCopy = angular.copy(raterOriginal),
            watchWarningMessageFromService = {};

        _.rater = raterOriginal;

        MessageBagService.addMessage({id: 'warningMessageFromService', template: 'Поле <b>«Наименование»</b> обязательно к заполнению.', className: 'warning', responseText: 'Обновить'});
        MessageBagService.addMessage({id: 'alertMessageFromService', template: 'Ошибка.', className: 'alert'});
        MessageBagService.addMessage({id: 'successMessageFromService', template: 'Поле <b>«Наименование»</b> заполнено.', className: 'success'});

        _.$watch(function() {
            return !angular.equals(raterOriginal, raterCopy);
        }, function(value) {
            if (value) {
                MessageBagService.addMessage({id: 'warningMessageFromService', template: 'Имеются несохраненные изменения.', className: 'warning', responseText: 'Обновить'});

                watchWarningMessageFromService = _.$watch(function() {
                    return angular.isDefined(MessageBagService.getMessageById('warningMessageFromService'))
                        ? MessageBagService.getMessageById('warningMessageFromService').getResponseStatus() : false;
                }, function(value) {
                    if (value) {
                        MessageBagService.deleteMessageById('warningMessageFromService');
                        MessageBagService.addMessage({id: 'successMessageFromService', template: 'Данные успешно обновлены.', className: 'success'});
                        raterCopy = angular.copy(raterOriginal);
                        watchWarningMessageFromService();
                    }
                }, true);
            } else if (angular.isFunction(watchWarningMessageFromService)) {
                MessageBagService.deleteMessageById('warningMessageFromService');
                watchWarningMessageFromService();
            }
        }, true);
    };

    controller.$inject = ['MessageBagService'];

    var service = function() {};

    angular
        .module('rater', [])
        .service('RaterService', service)
        .controller('RaterController', controller);
})();