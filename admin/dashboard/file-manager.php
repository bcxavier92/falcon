<?php
    require "../../core/falcon.php";
    require "../../core/dashboard.php";

    define("FnDashboardName", "FileManager");

    Falcon::startSession();
    FnDashboard::dashboardHeader();
?>

<?php FnDashboard::dashboardBody(); ?>

<iframe src="../file-modal.php" style="width: 800px; height: 600px; border: 1px solid #ccc;"></iframe>

<script src="../script/admin.js"></script>

<?php FnDashboard::dashboardFooter(); ?>