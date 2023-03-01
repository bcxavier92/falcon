<?php
    require "../../core/falcon.php";
    require "../../core/dashboard.php";

    define("FnDashboardName", "Settings");

    Falcon::startSession();
    FnDashboard::dashboardHeader();
?>

<link rel="stylesheet" href="../css/dashboard-settings.css">

<?php FnDashboard::dashboardBody(); ?>
<h1>Settings</h1>
<div class="card full-card">
    <h2>Site Settings</h2>
    <table id="settings-table" class="col-4-edit-table">
        <tr>
            <th class="col-1">Key</th>
            <th class="col-2">Value</th>
            <th class="col-3"></th>
            <th class="col-4"></th>
        </tr>
        <?php
            $index = 0;
            $settings = Falcon::getSettings();

            foreach($settings as $key => $value) {
                $htmlValue = htmlspecialchars($value);
                $htmlKey = htmlspecialchars($key);

                $editDisabled = $key === "fn_version";
                $disabledClass = $editDisabled ? " icon-disabled" : "";
                $editIconColors = $editDisabled ? "111111:cccccc" : "111111:317fdd";
                $deleteIconColors = $editDisabled ? "111111:cccccc" : "111111:c41400";

                echo "<tr id=\"settings-row-$index\">";
                echo "<td class=\"col-1\"><input id=\"setting-key-$index\" type=\"text\" value=\"$htmlKey\" readonly></td>";
                echo "<td class=\"col-2\"><input id=\"setting-value-$index\" type=\"text\" value=\"$htmlValue\" readonly></td>";
                echo "<td class=\"col-3\"><img class=\"edit-icon$disabledClass\" src=\"" . Falcon::getBaseUrl() . "/falcon/rest/load-svg-icon.php?name=edit&colors=$editIconColors\" alt=\"Edit\" data-index=\"$index\"></td>";
                echo "<td class=\"col-4\"><img class=\"delete-icon$disabledClass\" src=\"" . Falcon::getBaseUrl() . "/falcon/rest/load-svg-icon.php?name=x&colors=$deleteIconColors\" alt=\"Delete\" data-index=\"$index\"></td>";
                echo "</tr>";

                $index++;
            }

            $jsSettings = "let settingsCount = $index; const settingsCsrf = " . json_encode(Falcon::generateCsrfToken("fn-settings-update")) . ";\n";
        ?>
    </table>

    <button id="save-settings-button">Save</button>
    <a id="add-setting-button" href="#">+ Add Setting</a>
</div>

<script src="../script/admin.js"></script>
<script>
    <?php echo $jsSettings; ?>
    $(document).ready(function() {
        $(".edit-icon").click(function() {
            if($(this).hasClass("icon-disabled")) return;
            const index = $(this).attr("data-index");
            const valueInput = $("#setting-value-" + index);
            if(valueInput.attr("readonly")) {
                valueInput.removeAttr("readonly");
            } else {
                valueInput.attr("readonly", "");
            }
        });

        $(document).on("click", ".delete-icon", function() {
            const index = $(this).attr("data-index");
            const key = $("#setting-key-" + index).val();
            if(!confirm("Do you really want to delete \"" + key + "\"?")) return;

            $("#settings-row-" + index).remove();
            alert("\"" + key + "\" deleted. Save settings to take effect.");
        });

        $("#add-setting-button").click(function() {
            addSetting();
            return false;
        });

        $("#save-settings-button").click(function() {
            const formData = getTableSettings();
            if(formData) {
                formData["fn-token"] = settingsCsrf;
                const serialized = new URLSearchParams(Object.entries(formData)).toString();
                $.ajax({
                    type: "POST",
                    url: "../../rest/update-settings.php",
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
    });

    function addSetting() {
        const row = "<tr id=\"settings-row-" + settingsCount + "\">" +
            "<td class=\"col-1\"><input id=\"setting-key-" + settingsCount + "\" type=\"text\"></td>" +
            "<td class=\"col-2\"><input id=\"setting-value-" + settingsCount + "\" type=\"text\"></td>" +
            "<td class=\"col-3\"></td>" +
            "<td class=\"col-4\"><img class=\"delete-icon\" src=\"../../rest/load-svg-icon.php?name=x&colors=111111:c41400\" alt=\"Delete\" data-index=\"" + settingsCount + "\"></td>" +
            "</tr>";
        settingsCount++;

        $("#settings-table").append(row);
    }

    function getTableSettings() {
        let settings = [];
        for(let i = 0; i < settingsCount; i++) {
            if($("#setting-key-" + i).length && $("#setting-value-" + i).length) {
                let key = "_key-" + $("#setting-key-" + i).val();
                if(key in settings) {
                    alert("Duplicate key \"" + key.replace("_key-", "") + "\"");
                    return false;
                }
                settings[key] = $("#setting-value-" + i).val();
            }
        }

        return settings;
    }
</script>

<?php FnDashboard::dashboardFooter(); ?>