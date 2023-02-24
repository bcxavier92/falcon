<?php
    if(!defined("FalconLoaded")) die("Direct access prohibited");
    if(!defined("FnDashboardName")) die("Dashboard name required");

    FnDashboard::dashboardRequireAdmin();
    $settings = Falcon::getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Falcon Dashboard | <?php echo Falcon::getEscapedSiteName() . " | " . htmlspecialchars(FnDashboardName, ENT_QUOTES); ?></title>

    <script src="<?php Falcon::printBaseUrl(); ?>/falcon/admin/script/jquery-3.6.3.min.js"></script>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:400">

    <link rel="stylesheet" href="<?php Falcon::printBaseUrl(); ?>/falcon/admin/css/main.css">