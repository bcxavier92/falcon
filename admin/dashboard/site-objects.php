<?php
    require "../../core/falcon.php";
    require "../../core/dashboard.php";

    define("FnDashboardName", "SiteObjects");

    Falcon::startSession();
    FnDashboard::dashboardHeader();
?>

<link rel="stylesheet" href="../css/dashboard-site-objects.css">

<?php FnDashboard::dashboardBody(); ?>
<h1>Site Object Categories</h1>
<div class="card full-card">
    <div class="spoiler">
        <div class="spoiler-header">
            <h2>Create New</h2>
            <img src="<?php Falcon::printBaseUrl(); ?>/falcon/rest/load-svg-icon.php?name=triangle-down&colors=111111:333333" class="spoiler-arrow">
        </div>
        <div class="spoiler-content">
            <label id="new-category-name-label" for="new-category-name">Category Name:</label>
            <input type="text" id="new-category-name" name="new-category-name">
            <table id="new-site-category" class="col-4-edit-table">
                <tr>
                    <th class="col-1">Input Key</th>
                    <th class="col-2">Data Type</th>
                    <th class="col-3">Label</th>
                    <th class="col-4"></th>
                </tr>
            </table>
            <button id="create-site-category-button">Create</button>
            <a href="#" id="new-input-button">+ New Input</a>
        </div>
    </div>
</div>

<?php
    $conn = Falcon::getMysqlConnection();

    $stmtGetCategories = $conn->prepare("SELECT * FROM fn_site_object_categories;");
    $stmtGetCategories->execute();
    $categories = $stmtGetCategories->fetchAll(PDO::FETCH_ASSOC);

    foreach($categories as $catRow) {
        $cat = $catRow["category_name"];
        $struct = $catRow["structure"];
        $label = $catRow["label"];

        $stmtGetColumns = $conn->prepare("DESCRIBE fn_so_$cat;");
        $stmtGetColumns->execute();
        $cols = $stmtGetColumns->fetchAll(PDO::FETCH_COLUMN);

        $types = explode(",", $struct);

        echo "
            <div class=\"card full-card\">
                <div class=\"spoiler\">
                    <div class=\"spoiler-header\">
                        <span class=\"spoiler-header-inline\">
                            <h2>" . $cat . "</h2>
                            <img src=\"" . Falcon::getBaseUrl() . "/falcon/rest/load-svg-icon.php?name=edit&colors=111111:317fdd\" class=\"edit-category-name\">
                        </span>
                        <img src=\"" . Falcon::getBaseUrl() . "/falcon/rest/load-svg-icon.php?name=triangle-down&colors=111111:333333\" class=\"spoiler-arrow\">
                    </div>
                    <div class=\"spoiler-content\" style=\"display: none;\">
                        <table class=\"object-structure\">
                            <tr>
                                <th>Input Key</th>
                                <th>Type</th>
                            </tr>";
                                
                            $i = 0;
                            foreach($types as $type) {
                                $substrCol = substr($cols[$i + 1], 1);
                                $labelStyle = $label === $substrCol ? " style=\"background-color: #fff28f;\"" : "";

                                echo "<tr$labelStyle>";
                                echo "<td>" . $substrCol . "</td>"; // $i + 1 because column 0 is always object_index
                                echo "<td>$type</td>";
                                echo "</tr>";
                                $i++;
                            }

        echo "
                        </table>
                        <div>
                            <a href=\"site-object-single.php?cat=$cat\"><button style=\"margin-right: 15px;\">View Contents</button></a>
                            <a href=\"#\" class=\"migrate-button\">Migrate</a> | 
                            <a href=\"#\">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
?>

<?php echo "<script>let newCategoryCsrf = " . json_encode(Falcon::generateCsrfToken("fn-create-site-object-category")) . ";</script>\n"; ?>

<script src="../script/admin.js"></script>
<script>
    let categoryInputs = 0;

    function getNewCategoryTable() {
        let newCategory = [];
        for(let i = 0; i < categoryInputs; i++) {
            if($("#new-input-key-" + i).length && $("#new-data-type-" + i).length) {
                let key = "_key-" + $("#new-input-key-" + i).val();
                if(key in newCategory) {
                    alert("Duplicate key \"" + key.replace("_key-", "") + "\"");
                    return false;
                }
                newCategory[key] = $("#new-data-type-" + i).val();
            }
        }
        return newCategory;
    }

    function getObjectLabel() {
        let key = null;
        $(".label-toggle").each(function(i, item) {
            if($(item).prop("checked")) {
                let index = $(item).attr("data-index");
                key = $("#new-input-key-" + index).val();
            }
        });
        return key;
    }

    $(document).ready(function() {

        newCategoryAddRow();

        $("#create-site-category-button").click(function() {
            const formData = getNewCategoryTable();
            formData["category-name"] = $("#new-category-name").val();
            if(formData) {
                let label = getObjectLabel();
                if(label != null) {
                    formData["object-label"] = label;
                }
                formData["fn-token"] = newCategoryCsrf;
                const serialized = new URLSearchParams(Object.entries(formData)).toString();
                $.ajax({
                    type: "POST",
                    url: "../../rest/create-site-object-category.php",
                    data: serialized,
                    complete: function(data) {
                        var status = data.status;
                        var response = data.responseJSON.message;

                        if(status == 201 && response == "success") {
                            alert("Site object category created");
                            location.reload();
                        } else {
                            alert(response);
                        }
                    }
                });
            }
        });

        function newCategoryAddRow() {
            const rowHtml = 
            "<tr id=\"new-site-category-row-" + categoryInputs +  "\">" +
            "<td class=\"col-1\"><input type=\"text\" id=\"new-input-key-" + categoryInputs + "\"></td>" +
            "<td class=\"col-1\">" +
                "<select id=\"new-data-type-" + categoryInputs + "\">" +
                    "<option selected disabled>Data Type</option>" +
                    "<option value=\"object-ref\">Object Ref (varchar(32))</option>" +
                    "<option value=\"lang-ref\">Language File Ref (varchar(255))</option>" +
                    "<option value=\"file\">File (varchar(32))</option>" +
                    "<option value=\"short-text\">Short Text (varchar(255))</option>" +
                    "<option value=\"med-text\">Medium Text (varchar(2048))</option>" +
                    "<option value=\"long-text\">Long Text (text(65535))</option>" +
                    "<option value=\"huge-text\">Huge Text (mediumtext(16777215))</option>" +
                    "<option value=\"int\">Integer (int(4))</option>" +
                    "<option value=\"big-int\">Big Integer (bigint(8))</option>" +
                    "<option value=\"decimal\">Decimal (decimal(28, 10))</option>" +
                    "<option value=\"boolean\">Boolean (tinyint(1))</option>" +
                "</select>" +
            "</td>" +
            "<td class=\"col-3\"><input type=\"checkbox\" class=\"label-toggle\" data-index=\"" + categoryInputs + "\"></td>" +
            "<td class=\"col-4\"><img class=\"delete-icon\" src=\"<?php Falcon::printBaseUrl(); ?>/falcon/rest/load-svg-icon.php?name=x&colors=111111:c41400\" alt=\"Delete\" data-index=\"" + categoryInputs + "\"></td>"
            "</tr>";


            $("#new-site-category").append(rowHtml);
            
            categoryInputs++;
        }

        $("#new-input-button").click(function() {
            newCategoryAddRow();
            return false;
        });

        $(".edit-category-name").click(function(e) {
            e.preventDefault();
            alert("This feature is still under development");
            return false;
        });

        $(".migrate-button").click(function() {
            alert('This feature is still under development');
            return false;
        });

        $(document).on("click", ".label-toggle", function() {
            const index = $(this).attr("data-index");

            $(".label-toggle").each(function(i, item) {
                if($(item).attr("data-index") != index) {
                    $(item).prop("checked", false);
                }
            });
        });

        $(document).on("click", ".delete-icon", function() {
            const index = $(this).attr("data-index");
            $("#new-site-category-row-" + index).remove();
        });
    });
</script>

<?php FnDashboard::dashboardFooter(); ?>