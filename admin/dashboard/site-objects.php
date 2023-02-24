<?php
    require "../../core/falcon.php";
    require "../../core/dashboard.php";

    define("FnDashboardName", "Settings");

    Falcon::startSession();
    FnDashboard::dashboardHeader();
?>

<?php FnDashboard::dashboardBody(); ?>
<h1>Site Objects</h1>
<div class="card full-card">
    <div class="spoiler">
        <div class="spoiler-header">
            <h2>Create New</h2>
            <img src="<?php Falcon::printBaseUrl(); ?>/falcon/rest/load-svg-icon.php?name=triangle-down&colors=111111:333333" class="spoiler-arrow">
        </div>
        <div class="spoiler-content">
            <label>Object Name:</label>
            <input type="text" id="new-object-name">
            <table id="new-site-object" class="col-4-edit-table">
                <tr>
                    <th class="col-1">Input Key</th>
                    <th class="col-2">Data Type</th>
                    <th class="col-3"></th>
                </tr>
            </table>
            <button id="create-site-object-button">Create</button>
            <a href="#" id="new-input-button">+ New Input</a>
        </div>
    </div>
</div>

<?php echo "<script>let newSiteObjectCsrf = " . json_encode(Falcon::generateCsrfToken("fn-create-site-object")) . ";</script>\n"; ?>

<script>
    let objectInputs = 0;

    function getTableNewObject() {
        let newObject = [];
        for(let i = 0; i < objectInputs; i++) {
            if($("#new-input-key-" + i).length && $("#new-data-type-" + i).length) {
                let key = "_key-" + $("#new-input-key-" + i).val();
                if(key in newObject) {
                    alert("Duplicate key \"" + key.replace("_key-", "") + "\"");
                    return false;
                }
                newObject[key] = $("#new-data-type-" + i).val();
            }
        }
        return newObject;
    }

    $(document).ready(function() {
        $("#create-site-object-button").click(function() {
            const formData = getTableNewObject();
            formData["object-name"] = $("#new-object-name").val();
            if(formData) {
                formData["fn-token"] = newSiteObjectCsrf;
                const serialized = new URLSearchParams(Object.entries(formData)).toString();
                $.ajax({
                    type: "POST",
                    url: "../../rest/create-site-object.php",
                    data: serialized,
                    complete: function(data) {
                        var status = data.status;
                        var response = data.responseJSON.message;

                        if(status == 201 && response == "success") {
                            alert("Settings saved");
                            location.reload();
                        } else {
                            alert(response);
                        }
                    }
                });
            }
        });

        $("#new-input-button").click(function() {
            const rowHtml = 
            "<tr id=\"new-site-object-row-" + objectInputs +  "\">" +
            "<td class=\"col-1\"><input type=\"text\" id=\"new-input-key-" + objectInputs + "\" placeholder=\"Input Key\"></td>" +
            "<td class=\"col-1\">" +
                "<select id=\"new-data-type-" + objectInputs + "\">" +
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
            "<td class=\"col-3\"><img class=\"delete-icon\" src=\"<?php Falcon::printBaseUrl(); ?>/falcon/rest/load-svg-icon.php?name=x&colors=111111:c41400\" alt=\"Delete\" data-index=\"" + objectInputs + "\"></td>" +
            "</tr>";


            $("#new-site-object").append(rowHtml);
                objectInputs++;
                return false;
            });

            $(document).on("click", ".delete-icon", function() {
                const index = $(this).attr("data-index");
                $("#new-site-object-row-" + index).remove();
            });
    });
</script>

<?php FnDashboard::dashboardFooter(); ?>