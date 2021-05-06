(function ($) {
    $(function() {
        var today = new Date();

        if (today.getMonth() === 3 && today.getDate() === 1) { // 1 April
            $("a[href^='mailto:klachten@gewis.nl']")
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

        // Adds class to notify that the browser supports javascript
        // Useful when adding specific styling for javascript enabled browsers
        document.body.classList.add("js");

        // Make a table row clickable
        // Add the .table-clickable class to the <table> element and add the attribute
        // data-link containing the URL to each row.
        // NOTE: addEventListener() might not work in older versions of IE
        document.querySelector(".table-clickable").addEventListener("click", function(event) {
            var parentNode = event.target.parentNode;

            if (parentNode.nodeName === "TR" && parentNode.dataset.link) {
                window.location = parentNode.dataset.link;
            }
        });
    });
}(jQuery));
