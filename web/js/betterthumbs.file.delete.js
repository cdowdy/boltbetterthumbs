$(document).ready(function () {
 var boltPath = $('#boltPath').data("bolt-path");


    $(".image-container").on( 'click', ".deletebutton", function (event) {
        var img = this.id;
        var target = event.target;
        var spinner = $('<div class="cp-spinner cp-skeleton"></div><p>Removing Image</p>');
        $.ajax({
            type: "POST",
            url: boltPath + '/betterthumbs/files/delete',
            data: {'img': img},

            beforeSend: function () {
                $(target).parentsUntil(".image-container").children(".img-container").hide();
                $(target).hide();
                $(target).parent().append(spinner);
            },

            success: function () {
                $(target).hide();
                $(target).siblings(spinner).hide();
                $(target).before('<p class="removed-success">' + img + ' Has been removed from the cache</p>');
            },

            error: function (xhr, desc, err) {
                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        });
    });

    $(".delete-all").on('click', function (event) {
        var all = $(".delete-all").data("all");
        var spinner = $('<div class="cp-spinner cp-skeleton"></div><p>Removing All Images</p>');
        var target = event.target;
        var singleContainer = $("#image-grid");

        $.ajax({
            type: "POST",
            url: boltPath + '/betterthumbs/files/delete/all',
            data: { 'all' : all },

            beforeSend: function () {
                $(target).parent().append(spinner);
                $(target).hide();

            },

            success: function () {
                $(target).hide();
                $(target).siblings(spinner).hide();
                $(target).before('<p class="removed-success">All Images Have Been Removed From The Cache</p>');
                singleContainer.empty();
                singleContainer.append('<div class="column"><p>No Images In Cache</p></div>');
            },

            error: function (xhr, desc, err) {
                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
                $(target).parent().append(xhr.responseText);
            }
        });
    });




});

