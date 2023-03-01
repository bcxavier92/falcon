$(document).ready(function() {
    $(document).on("click", ".spoiler-header", function() {
        let spoilerContent = $(this).parent().find(".spoiler-content");
        let display = spoilerContent.css("display");
        spoilerContent.css("display", display == "none" ? "block" : "none");
    });
});