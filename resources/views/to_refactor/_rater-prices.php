<?php header("Content-type: text-html; charset=utf-8;"); ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="../../assets/vendor/foundation/css/foundation-flex.css">
        <link rel="stylesheet" href="../../assets/vendor/foundation-icons/foundation-icons.css">
        <link rel="stylesheet" href="../../assets/style/app.css">
    </head>
    <body data-ng-app="poipoteke">
        <nav>
            <div class="row collapse">
                <div class="large-12 columns">
                    <ul class="menu">
                        <li><a href="/PreWork/prototype/rater.php" title="Информация о компании"><i class="fi-paperclip"></i><span class="hide-for-small-only">&nbsp;О нас</span></a></li>
                        <li><a href="/PreWork/prototype/rater-affiliates.php" title="Филиалы"><i class="fi-map"></i><span class="hide-for-small-only">&nbsp;Филиалы</span></a></li>
                        <li><a href="/PreWork/prototype/rater-work-time.php" title="Настройка рабочего времени"><i class="fi-clock"></i><span class="hide-for-small-only">&nbsp;Рабочее время</span></a></li>
                        <li><a href="/PreWork/prototype/rater-prices.php" class="active" title="Настройка цен"><i class="fi-pricetag-multiple"></i><span class="hide-for-small-only">&nbsp;Цены</span></a></li>
                        <li><a href="/PreWork/prototype/rater-requests.php" class="button" title="Заявки на оценку"><i class="fi-credit-card"></i><span class="hide-for-small-only">&nbsp;Заявки</span></a></li>
                        <li><a href="/PreWork/prototype" class="logout" title="Выход"><i class="fi-key"></i></a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="divider-medium"></div>
        <div class="row">
            <div class="large-12 columns">
                <header>
                    <h3 class="subheader"><i class="fi-pricetag-multiple"></i>&nbsp;Настройка цен</h3>
                </header>
                <hr>
            </div>
        </div>
        <div class="divider-medium"></div>
        <form class="rater-settings">
            <div class="row">
                <div class="large-6 columns">
                    <label>Наименование<input type="text" name="title"></label>
                    <label>E-mail<input type="text" name="title"></label>
                    <label>О нас<textarea name="requisites"></textarea></label>
                </div>
                <div class="large-6 columns">
                    <label>Реквизиты организации<textarea name="requisites"></textarea></label>
                </div>
            </div>
            <div class="row">
                <div class="large-12 columns text-right">
                    <input type="submit" class="button" value="Обновить">
                </div>
            </div>
        </form>
        <div class="divider-large"></div>
        <div class="overlay"></div>

        <script type="text/javascript" src="../../assets/vendor/angular/angular.min.js"></script>
        <script type="text/javascript" src="../../assets/vendor/angular-360-no-scope/angular-360-no-scope.js"></script>
        <script type="text/javascript" src="../../assets/vendor/angular-yandex-map/ya-map-2.1.min.js"></script>
        <script type="text/javascript" src="../../assets/js/app.js"></script>
    </body>
</html>
