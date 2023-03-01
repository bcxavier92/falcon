<?php
    require "../core/falcon.php";

    Falcon::startSession();

    if(!Falcon::isAdmin()) {
        header("Location: " . Falcon::getSettings()["base_url"] . "/falcon/admin/");
        die();
    }
?>

<link rel="stylesheet" href="css/main.css">
<style>
    body {
        background-color: #fff;
    }

    #modal {
        width: 100%;
        height: 100%;
        background-color: #fff;
    }

    #modal-top-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 60px;
        background-color: #fff;
        border-bottom: 1px solid #ccc;
    }

    #modal-top-bar * {
        display: inline-block;
    }

    #modal-files {
        padding-top: 70px;
    }

    #modal-files-table {
        width: 100%;
    }

    th,
    td {
        padding-right: 25px;
    }

    .modal-image-name-container {
        display: flex;
        align-items: center;
    }

    .modal-image-icon-container {
        display: inline-block;
        width: 60px;
        height: 48px;
        margin-right: 10px;
        background-position: center;
        background-size: contain;
        background-repeat: no-repeat;
    }

    .selected-row {
        background-color: #b1edfa;
    }

    #modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #edit-popup {
        position: absolute;
        display: flex;
        flex-direction: column;
        width: 275px;
        padding: 20px;
        background-color: #fff;
    }
</style>

<div id="modal">
    <div id="modal-top-bar">
        <form id="file-upload-form" method="POST" action="<?php Falcon::printBaseUrl(); ?>/falcon/rest/upload-files.php" enctype="multipart/form-data">
            <input style="display: none;" name="file[]" type="file" id="file-upload-input" multiple>
            <?php Falcon::printFormCsrfToken("fn-upload-files"); ?>
            <button id="choose-files-button">↑</button>
        </form>
        <button id="edit-button">e</button>
    </div>
    <div id="modal-files">
        <table id="modal-files-table">
            <tr>
                <th>Name</th>
                <th>Modified</th>
                <th>Size</th>
                <th>Permissions</th>
            </tr>
            <?php
                $conn = Falcon::getMysqlConnection();
                $stmtGetUploads = $conn->prepare("SELECT * FROM fn_uploads;");
                $stmtGetUploads->execute();
                $uploads = $stmtGetUploads->fetchAll(PDO::FETCH_ASSOC);

                foreach($uploads as $upload) {
                    $fileName = $upload["file_path"];
                    $filePath = __DIR__ . "/../uploads/$fileName";
                    if($fileName != "." && $fileName != ".." && $fileName != "" && file_exists($filePath)) {
                        $pathInfo = pathinfo($filePath);

                        $imgSrc = Falcon::getBaseUrl() . "/falcon/rest/load-svg-icon.php?name=file&colors=111111:333333";
                        if(is_dir($filePath)) {
                            $imgSrc = Falcon::getBaseUrl() . "/falcon/rest/load-svg-icon.php?name=folder&colors=111111:333333";
                        } else if(isset($pathInfo["extension"])) {
                            $extension = $pathInfo["extension"];
                            $images = ["jpg", "jpeg", "png", "webp", "svg", "gif"];
                            if(in_array($extension, $images)) {
                                $imgSrc = "../uploads/$fileName";
                            }
                        }

                        $urlPath = rawurlencode($fileName);
                        $urlPath = str_replace("%3A", ":", $urlPath);
                        $urlPath = str_replace("%2F", "/", $urlPath);
                        $urlPath = str_replace("%3F", "?", $urlPath);
                        $urlPath = str_replace("%26", "&", $urlPath);
                        $urlPath = str_replace("%23", "#", $urlPath);
            ?>

                        <tr class="file-row" data-uuid="<?php echo $upload["uuid"]; ?>">
                            <td>
                                <span class="modal-image-name-container">
                                    <div class="modal-image-icon-container" style="background-image: url('<?php echo $imgSrc; ?>');"></div>
                                    <a href="<?php echo Falcon::getBaseUrl() . "/falcon/uploads/$urlPath"; ?>" target="_blank"><?php echo $fileName; ?></a>
                                </span>
                            </td>
                            <td><?php echo date("M j, Y g:i:s a", filemtime($filePath)); ?></td>
                            <td><?php echo getFileSize($filePath); ?></td>
                            <td><?php echo fileperms($filePath); ?></td>
                        </tr>
            <?php
                    }
                }
            ?>
        </table>
    </div>

    <div id="modal-overlay">
        <div id="edit-popup">
            <form action="">
                <p>The Title</p>
                <p>babai72edb1ibahdaia</p>
                <label for="alt-text">Image Alt Text</label>
                <input type="text" name="alt-text">
                <input type="submit" value="Save">
            </form>
        </div>
    </div>
</div>

<div id="chosen-uuid" style="display: none;"></div>

<script src="script/jquery-3.6.3.min.js"></script>
<script>
    let selected = null;
    $(document).ready(function() {
        $("#file-upload-form").submit(function(e) {
            e.preventDefault();
            var form = this;

            $.ajax({
                type: $(form).attr("method"),
                url: $(form).attr("action"),
                data: new FormData(form),
                cache: false,
                contentType: false,
                processData: false,
                complete: function(data) {
                    var status = data.status;
                    var response = data.responseJSON.message;

                    if(status == 201 && response == "success") {
                        window.location.reload();
                    } else {
                        alert(response);
                    }
                }
            });
        });

        $("#choose-files-button").click(function(e) {
            e.preventDefault();
            $("#file-upload-input").click();
        });

        $("#file-upload-input").change(function() {
            let length = $("#file-upload-input")[0].files.length;
            if(length) {
                console.log("Submitting...");
                $("#file-upload-form").submit();
            }
        });

        $(".file-row").click(function() {
            $(".file-row").removeClass("selected-row");
            $(this).addClass("selected-row");
            selected = $(this).attr("data-uuid");
            $("#chosen-uuid").html(selected);
        });

        $("#edit-button").click(function() {
            if(selected) {

            }
        });
    });
</script>

<?php
    function getFileSize($filePath) {
        $bytes = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }
        return round($bytes, 2) . ' ' . $units[$index];
    }
?>