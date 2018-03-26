<?php header("Content-type: text-html; charset=utf-8;"); ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="../../assets/vendor/foundation/css/foundation-flex.css">
        <link rel="stylesheet" href="../../assets/vendor/foundation-icons/foundation-icons.css">
        <link rel="stylesheet" href="../../assets/style/app.css">
    </head>
    <body data-ng-app="poipoteke" data-ng-controller="AppController as App"">
        <nav>
            <div class="row collapse">
                <div class="small-12 columns">
                    <ul class="menu">
                        <li><a href="/PreWork/prototype/rater.php" class="active" title="Информация о компании"><i class="fi-paperclip"></i><span class="hide-for-small-only">&nbsp;О нас</span></a></li>
                        <li><a href="/PreWork/prototype/rater-affiliates.php" title="Филиалы"><i class="fi-map"></i><span class="hide-for-small-only">&nbsp;Филиалы</span></a></li>
                        <li><a href="/PreWork/prototype/rater-work-time.php" title="Настройка рабочего времени"><i class="fi-clock"></i><span class="hide-for-small-only">&nbsp;Рабочее время</span></a></li>
                        <li><a href="/PreWork/prototype/rater-prices.php" title="Настройка цен"><i class="fi-pricetag-multiple"></i><span class="hide-for-small-only">&nbsp;Цены</span></a></li>
                        <li><a href="/PreWork/prototype/rater-requests.php" class="button" title="Заявки на оценку"><i class="fi-credit-card"></i><span class="hide-for-small-only">&nbsp;Заявки</span></a></li>
                        <li><a href="/PreWork/prototype" class="logout" title="Выход"><i class="fi-key"></i></a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="divider-medium"></div>
        <div class="row">
            <div class="small-12 columns">
                <header>
                    <h3 class="subheader"><i class="fi-paperclip"></i>&nbsp;Информация о компании</h3>
                </header>
                <hr>
            </div>
        </div>
        <div class="divider-medium"></div>
        <div data-message-bag></div>
        <div class="divider-small"></div>
        <div class="row">
            <textarea class="">{"questions":[{"text":"question text", "answers": [{"text": "answer text", "mark": "answer mark"}]}]}</textarea>
            <data-textarea-value></data-textarea-value>
            <div class="small-12 large-6 columns">
                <p data-editable-text data-label="Наименование организации" required="true" placeholder="Company name" data-model="App.rater.title"></p>
                <p data-editable-text data-label="E-mail" required="true" placeholder="Company mail" data-model="App.rater.email"></p>
                <p data-editable-longtext data-label="О нас" data-model="App.rater.about"></p>
            </div>
            <div class="small-12 large-6 columns">
                <p data-editable-longtext data-label="Реквизиты" data-model="App.rater.requisites"></p>
            </div>
        </div>
        <div class="divider-large"></div>
        <div class="overlay"></div>

        <script type="text/javascript" src="../../assets/vendor/angular/angular.min.js"></script>
        <script type="text/javascript" src="../../assets/vendor/angular-sanitize/angular-sanitize.min.js"></script>
        <script type="text/javascript" src="../../assets/vendor/angular-360-no-scope/angular-360-no-scope.js"></script>
        <script type="text/javascript" src="../../assets/vendor/angular-yandex-map/ya-map-2.1.min.js"></script>
        <script type="text/javascript" src="../../assets/js/app.js"></script>
    </body>
</html>
