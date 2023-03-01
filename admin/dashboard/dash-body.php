<?php
    if(!defined("FalconLoaded")) die("Direct access prohibited");
    FnDashboard::dashboardRequireAdmin();
?>
</head>
<body>
    <div id="nav">
        <img src="../image/logo.png" alt="Falcon" id="nav-logo">
        <ul id="nav-links">
            <li><a href="<?php Falcon::printBaseUrl(); ?>/falcon/admin/dashboard/">Dashboard</a></li>
            <li><a href="<?php Falcon::printBaseUrl(); ?>/falcon/admin/dashboard/site-objects.php">Site Objects</a></li>
            <?php
                if(FnDashboardName === "SiteObjects" || substr(FnDashboardName, 0, 12) === "SiteObjects/") {
                    $stmtGetCategories = Falcon::getMysqlConnection()->prepare("SELECT category_name FROM fn_site_object_categories;");
                    $stmtGetCategories->execute();
                    $categories = $stmtGetCategories->fetchAll(PDO::FETCH_COLUMN);

                    foreach($categories as $cat) {
                        echo "<li><a href=\"" . Falcon::getBaseUrl() . "/falcon/admin/dashboard/site-object-single.php?cat=$cat\">- $cat</a></li>";
                    }
                }
            ?>
            <li><a href="<?php Falcon::printBaseUrl(); ?>/falcon/admin/dashboard/file-manager.php">File Manager</a></li>
            <li><a href="<?php Falcon::printBaseUrl(); ?>/falcon/admin/dashboard/settings.php">Settings</a></li>
        </ul>
    </div>
    <div id="content">
        <div id="content-inner">