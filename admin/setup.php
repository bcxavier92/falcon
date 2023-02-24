<?php
    define("FnSkipInit", true);
    require "../core/falcon.php";

    if(FnSetup::hasBeenSetup()) {
        die("Falcon has already been setup");
    }

    // NOTE: No session is necessary here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falcon Setup</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500">

    <link rel="stylesheet" href="css/main.css" >
    <link rel="stylesheet" href="css/setup.css" >
</head>
<body>
    <div id="setup-card" class="card">
        <img src="image/logo.png" alt="Falcon" style="width: 200px;">
        <p id="version-info">Falcon <?php echo Falcon::getVersion() . " " . Falcon::getVersionName(); ?> developed by <a href="https://xaviervisuals.com/" target="_blank" rel="nofollow">Xavier Visuals</a></p>
        <form action="../rest/setup.php" method="POST" id="setup-form">

            <div id="section-1">
                <p class="section-title">Site Setup</p>
                <label for="site-name">Site Name:</label>
                <input type="text" name="site-name" id="site-name" placeholder="Falcon Site">
                <label for="json-path">Secure JSON Path:</label>
                <p style="font-size: 0.7em; color: #777;">rw permissions are required on an existing file</p>
                <input type="text" name="json-path" id="json-path" placeholder="/var/falcon.json">
            </div>

            <div id="section-2">
                <p class="section-title">MySQL Database</p>
                <label for="host">Host:</label>
                <input type="text" name="mysql-host" id="host">
                <label for="port">Port:</label>
                <input type="text" name="mysql-port" id="port">
                <label for="database">Database Name:</label>
                <input type="text" name="mysql-database" id="database">
                <label for="mysql-user">Username:</label>
                <input type="text" name="mysql-user" id="mysql-user">
                <label for="mysql-password">Password:</label>
                <input type="password" name="mysql-password" id="mysql-password">
            </div>

            <div id="section-3">
                <p class="section-title">Admin Account</p>
                <label for="username">Username:</label>
                <input type="text" name="username" id="username">
                <label for="username">Email:</label>
                <input type="email" name="email" id="email">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <label for="confirm-password">Confirm Password:</label>
                <input type="password" name="confirm-password" id="confirm-password">
            </div>

            <div id="response-wrapper">
                <p id="response"></p>
            </div>

            <div id="button-row">
                <button id="button-back">&lt; Back</button>
                <button id="button-next">Next &gt;</button>
                <input id="button-submit" type="submit" value="Finish">
            </div>
        </form>
    </div>

    <script src="script/jquery-3.6.3.min.js"></script>
    <script>
        var section = 1;
        $(document).ready(function() {
            // Set visible section
            function setSection(num) {
                switch(num) {
                    case 1:
                        $("#section-1").css("display", "block");
                        $("#section-2").css("display", "none");
                        $("#section-3").css("display", "none");
                        
                        $("#button-back").css("display", "none");
                        $("#button-next").css("display", "block");
                        $("#button-submit").css("display", "none");
                        section = num;
                        break;
                    case 2:
                        $("#section-1").css("display", "none");
                        $("#section-2").css("display", "block");
                        $("#section-3").css("display", "none");
                        
                        $("#button-back").css("display", "block");
                        $("#button-next").css("display", "block");
                        $("#button-submit").css("display", "none");
                        section = num;
                        break;
                    case 3:
                        $("#section-1").css("display", "none");
                        $("#section-2").css("display", "none");
                        $("#section-3").css("display", "block");
                        
                        $("#button-back").css("display", "block");
                        $("#button-next").css("display", "none");
                        $("#button-submit").css("display", "block");
                        section = num;
                        break;
                    default:
                        break;
                }
            }

            // Next button
            $("#button-next").click(function(e) {
                e.preventDefault();
                if(section < 3) {
                    setSection(section + 1);
                }
            });

            // Back button
            $("#button-back").click(function(e) {
                e.preventDefault();
                if(section > 1) {
                    setSection(section - 1);
                }
            });

            // Submit setup form
            $("#setup-form").submit(function(e) {
                e.preventDefault();
                var form = $(this);

                $.ajax({
                    type: form.attr("method"),
                    url: form.attr("action"),
                    data: form.serialize(),
                    complete: function(data) {
                        var status = data.status;
                        var response = data.responseJSON.message;

                        if(status == 201 && response == "success") {
                            $("#setup-form").html("<p style=\"text-align: center;\">Setup successful! Redirecting to login...</p>");
                            setTimeout(function() {
                                window.location.replace(data.responseJSON.redirect);
                            }, 3000);
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