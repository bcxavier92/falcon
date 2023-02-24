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
            <li><a href="#">File Manager</a></li>
            <li><a href="<?php Falcon::printBaseUrl(); ?>/falcon/admin/dashboard/settings.php">Settings</a></li>
        </ul>
    </div>
    <div id="content">
        <div id="content-inner">