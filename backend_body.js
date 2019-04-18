$(function() {
    $("span.resize_defaults").unbind("click").on("click",function(e) {
        var size = $(this).data("value");
        $("input#resize_width").val(size);
        $("input#resize_height").val(size);
    });
    $("span.resize_defaults_thumb").unbind("click").on("click",function(e) {
        var size = $(this).data("value");
        $("input#thumb_width").val(size);
        $("input#thumb_height").val(size);
    });
});