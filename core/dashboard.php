<?php
    if(!defined("FalconLoaded")) die("Direct access prohibited");

    class FnDashboard {
        // Includes dashboard body
        static function dashboardBody() {
            include __DIR__ . "/../admin/dashboard/dash-body.php";
        }

        // Includes dashboard footer
        static function dashboardFooter() {
            include __DIR__ . "/../admin/dashboard/dash-footer.php";
        }

        // Includes dashboard header
        static function dashboardHeader() {
            include __DIR__ . "/../admin/dashboard/dash-header.php";
        }
        
        // Redirects to dashboard login if not admin
        static function dashboardRequireAdmin() {
            if(!Falcon::isAdmin()) {
                header("Location: " . Falcon::getSettings()["base_url"] . "/falcon/admin/");
                die();
            }
        }
    }
?>