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
                        <li><a href="/PreWork/prototype/rater-work-time.php" class="active" title="Настройка рабочего времени"><i class="fi-clock"></i><span class="hide-for-small-only">&nbsp;Рабочее время</span></a></li>
                        <li><a href="/PreWork/prototype/rater-prices.php" title="Настройка цен"><i class="fi-pricetag-multiple"></i><span class="hide-for-small-only">&nbsp;Цены</span></a></li>
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
                    <h3 class="subheader"><i class="fi-clock"></i>&nbsp;Настройка рабочего времени</h3>
                </header>
                <hr>
            </div>
        </div>
        <div class="divider-medium"></div>
        <form class="rater-settings">
            <div class="row show-for-large-only">
                <div class="large-4 columns">
                    <h5>День недели</h5>
                </div>
                <div class="large-4 columns">
                    <h5>Начало рабочего дня</h5>
                </div>
                <div class="large-4 columns">
                    <h5>Окончание рабочего дня</h5>
                </div>
            </div>
            <div class="divider-medium"></div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;понедельник</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
            </div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;вторник</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
            </div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;среда</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
            </div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;четверг</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
            </div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;пятница</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
            </div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;суббота</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
            </div>
            <div class="row">
                <div class="medium-12 large-4 columns">
                    <label><input type="checkbox" name="title"><i class="fi-power"></i>&nbsp;воскресенье</label>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-start">
                                <option value="8">08</option>
                                <option value="9">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-start">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="small-12 medium-6 large-4 columns">
                    <div class="row">
                        <div class="large-6 columns text-right">
                            <select name="hour-end">
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                            </select>
                        </div>
                        <div class="large-6 columns text-left">
                            <select name="minute-end">
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="large-12 columns"><hr></div>
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
