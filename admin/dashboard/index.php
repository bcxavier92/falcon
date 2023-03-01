<?php
    require "../../core/falcon.php";
    require "../../core/dashboard.php";

    define("FnDashboardName", "Overview");

    Falcon::startSession();
    FnDashboard::dashboardHeader();
?>

<?php FnDashboard::dashboardBody(); ?>
<h1>Overview</h1>
<div class="card full-card">
    <h2>Welcome to Falcon <?php echo Falcon::getVersion() . " " . Falcon::getVersionName(); ?></h2>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Illo officia recusandae aperiam cum odit vel eveniet? Doloribus quae eligendi quis delectus voluptates rerum soluta aspernatur tempora voluptatem natus aliquid molestiae commodi illo est corrupti, eius veritatis minima impedit et aperiam? Maiores laudantium maxime modi accusantium assumenda dolores corrupti vero officiis facilis! Cumque ex repellat omnis delectus, deserunt enim expedita et!</p>
</div>
<script src="../script/admin.js"></script>
<?php FnDashboard::dashboardFooter(); ?>