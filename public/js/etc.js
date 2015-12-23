(function ($) {
    $(function() {
        var today = new Date();

        if (today.getMonth() === 3 && today.getDate() === 1) { // 1 April
            $("a[href^='mailto:klachten@gewis.nl']")
                .attr("id", "dealWithIt")
                .mouseenter(function(){
                    $("<audio></audio>").attr({
                        "id": "na-na-na",
                        "src": "/etc/na-na-na.mp3",
                        "volume": 1,
                        "autoplay": "autoplay",
                        "loop": "loop"
                    }).appendTo("body");
                })
                .mouseleave(function(){
                    $("#na-na-na").remove();
                });
        }
    });
}(jQuery));
