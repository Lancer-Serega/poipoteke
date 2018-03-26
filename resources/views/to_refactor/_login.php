<?php header("Content-type: text-html; charset=utf-8;"); ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Авторизация</title>
        <link rel="stylesheet" href="../../assets/vendor/foundation/css/foundation-flex.css">
        <link rel="stylesheet" href="../../assets/vendor/foundation-icons/foundation-icons.css">
        <link rel="stylesheet" href="../../assets/style/app.css">
    </head>
    <body data-ng-app="poipoteke">
        <div class="divider-medium"></div>
        <div class="row align-center">
            <div class="medium-6 medium-centered large-4 large-centered columns">
                <form>
                    <div class="row log-in-form">
                        <div class="small-12 columns text-center"><h4 class="text-center">Для входа используйте<br>e-mail</h4></div>
                        <div class="small-12 columns"><label>Email<input type="text" placeholder="somebody@example.com" name="_username"></label></div>
                        <div class="small-12 columns"><label>Password<input type="password" placeholder="пароль" name="_userpass"></label></div>
                        <div class="small-12 columns"><a type="submit" class="button expanded">Войти</a></div>
                        <div class="small-12 columns text-center"><a href="#">забыли пароль?</a></div>
                    </div>
                </form>
            </div>
        </div>
        <div class="divider-medium"></div>
    </body>
</html>
