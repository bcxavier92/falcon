<?php
    require "../../core/falcon.php";
    require "../../core/dashboard.php";

    define("FnDashboardName", "SiteObjects/Single");

    Falcon::startSession();
    FnDashboard::dashboardHeader();
?>

<?php 
    FnDashboard::dashboardBody(); 
    $structure = false;
    if(Falcon::isDefined($_GET, "cat")) {
        $cat = $_GET["cat"];
        $conn = Falcon::getMysqlConnection();
        $stmtGetStructure = $conn->prepare("SELECT structure FROM fn_site_object_categories WHERE category_name=?;");
        $stmtGetStructure->execute([$cat]);
        $structure = $stmtGetStructure->fetch(PDO::FETCH_COLUMN);
    }

    if($structure) {
?>
<h1><?php echo $cat; ?></h1>
<div class="card full-card">
    <div class="spoiler">
        <div class="spoiler-header">
            <h2>Add Site Object</h2>
            <img src="<?php Falcon::printBaseUrl(); ?>/falcon/rest/load-svg-icon.php?name=triangle-down&colors=111111:333333" class="spoiler-arrow">
        </div>
        <div class="spoiler-content">
            <form id="create-site-object" method="POST" action="../../rest/create-site-object.php">
                <input type="text" name="cat" style="display: none;" value="<?php echo $cat; ?>">
                <?php
                    $types = explode(",", $structure);

                    $stmtGetColumns = $conn->prepare("DESCRIBE fn_so_$cat;");
                    $stmtGetColumns->execute();
                    $cols = $stmtGetColumns->fetchAll(PDO::FETCH_COLUMN);

                    $i = 0;
                    foreach($types as $type) {
                        $col = substr($cols[$i + 1], 1);
                        echo "
                            <label>$col ($type): </label>
                            <input type=\"text\" name=\"_val-$col\"></input>
                            <br>
                        ";
                        $i++;
                    }

                    Falcon::printFormCsrfToken("fn-create-site-object");
                ?>
                <input type="submit" value="Save">
            </form>
        </div>
    </div>
</div>

<iframe id="file-modal" src="../file-modal.php" style="width: 800px; height: 600px;"></iframe>
<script>
    setInterval(function() {
        let uuid = $("#file-modal").contents().find("#chosen-uuid").html();
        console.log(uuid);
    }, 1000);
</script>

    <div class="card-2-container">
    <?php
        $stmtGetLabel = $conn->prepare("SELECT label FROM fn_site_object_categories WHERE category_name=?;");
        $stmtGetLabel->execute([$cat]);
        $label = $stmtGetLabel->fetch(PDO::FETCH_COLUMN);

        $stmtGetSiteObjects = $conn->prepare("SELECT * FROM fn_so_$cat ORDER BY object_index;");
        $stmtGetSiteObjects->execute();
        $objects = $stmtGetSiteObjects->fetchAll(PDO::FETCH_ASSOC);
        
        $i = 0;
        foreach($objects as $obj) {
            ?>
                <div class="card">
                    <div class="spoiler">
                        <div class="spoiler-header">
                            <h3><?php echo $obj["_$label"]; ?></h3>
                            <img src="<?php Falcon::printBaseUrl(); ?>/falcon/rest/load-svg-icon.php?name=triangle-down&colors=111111:333333" class="spoiler-arrow">
                        </div>
                        <div class="spoiler-content">
                            <?php
                                foreach(array_keys($obj) as $key) {
                                    if($key !== "object_index") {
                                        $substrKey = substr($key, 1);
                                        echo "<p>$substrKey: " . $obj[$key] . "</p>";
                                    }
                                }
                            ?>
                        </div>
                    </div>
                </div>
            <?php
            $i++;
        }
    ?>
    </div>

<?php
    } else {
?>
    <h2>Site object category does not exist</h2>
<?php
    }
?>

<script src="../script/admin.js"></script>
<script>
    $(document).ready(function() {
        $("#create-site-object").submit(function(e) {
            e.preventDefault();
            var form = $(this);

            $.ajax({
                type: form.attr("method"),
                url: form.attr("action"),
                data: form.serialize(),
                complete: function(data) {
                    console.log(data.responseText);
                //     var status = data.status;
                //     var response = data.responseJSON.message;

                //     if(status == 201 && response == "success") {
                //         window.location.replace(data.responseJSON.redirect);
                //     } else {
                //         alert(response);
                //     }
                }
            });
        });
    });
</script>

<?php FnDashboard::dashboardFooter(); ?>