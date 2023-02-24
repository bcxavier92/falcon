<?php
    require "../core/falcon.php";

    Falcon::startSession();

    if(Falcon::isAdmin()) {
        header("Location: " . Falcon::getBaseUrl() . "/falcon/admin/dashboard/");
        die();
    }

    $settings = Falcon::getSettings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falcon Login | <?php echo Falcon::getEscapedSiteName(); ?></title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500">

    <link rel="stylesheet" href="css/main.css" >
    <link rel="stylesheet" href="css/login.css" >
</head>
<body>
    <div id="login-card" class="card">
        <img src="image/logo.png" alt="Falcon" style="width: 200px;">
        <p id="version-info">Falcon <?php echo Falcon::getVersion() . " " . Falcon::getVersionName(); ?> developed by <a href="https://xaviervisuals.com/" target="_blank" rel="nofollow">Xavier Visuals</a></p>
        <form action="../rest/admin-login.php" method="POST" id="login-form">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password">
            <?php Falcon::printFormCsrfToken("fn-admin-login"); ?>
            <div id="response-wrapper">
                <p id="response"></p>
            </div>
            <input type="submit" value="Login">
        </form>
    </div>

    <script src="script/jquery-3.6.3.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#login-form").submit(function(e) {
                e.preventDefault();
                var form = $(this);

                $.ajax({
                    type: form.attr("method"),
                    url: form.attr("action"),
                    data: form.serialize(),
                    complete: function(data) {
                        var status = data.status;
                        var response = data.responseJSON.message;

                        if(status == 200 && response == "success") {
                            window.location.replace(data.responseJSON.redirect);
                        } else {
                            $("#response").html(response);
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>