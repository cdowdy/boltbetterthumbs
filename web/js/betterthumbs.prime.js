$(document).ready(function () {
    var spinner = $('<span class="cp-spinner cp-skeleton"></span><p>Creating Images</p>');
    var formFieldset = $(".fieldset");


    formFieldset.on({

        click: function (event) {
            var target = event.target;
            var img = target.getAttribute('data-bthumb-name');
            var configData = $(target).parent().prev().find('.prime-cache-dropdown').val();
            var singleData = $(target).parent().prev().find('.prime-cache-single-input').val();
            var presetData = target.getAttribute('data-presets');


            if (target.classList.contains('prime-config')) {
                prime(target, img, configData, 'config');
            }

            if (target.classList.contains('prime-presets')) {
                prime(target, img, presetData, 'presets');
            }

            if (target.classList.contains('prime-single')) {
                if( singleData === "" ) {
                    $(target).parent().prev().find(".prime-single-error").removeAttr("hidden");
                    return false;
                } else {
                    console.log(singleData);
                    prime(target, img, singleData, 'single');
                }

            }
        },

        change: function (event) {
            var target = event.target;
            var code = event.keyCode || event.which;

            if ($(target).hasClass('prime-cache-single-input') && $(".prime-cache-single-input").length > 0 ) {
                $(target).parent().next().attr("hidden", "hidden");
            }
        },


        keypress: function (event) {
            var target = event.target;
            var code = event.keyCode || event.which;
            var buttonToClick = $(target).parent().parent().next().find("BUTTON");
            var img = $(target).parent().parent().next().find("BUTTON").data("bthumb-name");
            var modType = $(target).val();
            var presetData = $(target).data('presets');

            if ( code === 13 ) {
                event.preventDefault();

                if (buttonToClick.hasClass('prime-config')) {
                    $(target).on('click', prime(buttonToClick, img, modType, 'config' ) );
                }
                if ($(target).hasClass('prime-presets') ) {
                    $(target).on('click', prime('.prime-presets', img, presetData, 'presets' ) );
                }

                if (buttonToClick.hasClass('prime-single') ) {

                    if ($(target).val() === "") {
                        $(target).parent().next().removeAttr("hidden");
                        return false;
                    } else {
                        $(target).on('click', prime(buttonToClick, img, modType, 'single') );
                    }

                }

            }
        }

    });


    var prime = function (target, img, configName, modType) {
        $.ajax({
            type: "POST",
            url: $( "BODY").data("bolt-path") + '/betterthumbs/files/prime/do',
            data: {
                'modType' : modType,
                'configName' : configName,
                'image' : img
            },

            beforeSend: function () {

                showSpinner(target);
                disableButtons();

            },

            success: function () {
                enableButtons();
                removeSpinner(target);
            },

            error: function (xhr, desc, err) {
                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        });
    };
    

    function showSpinner(target) {
        $(target).hide();
        $(target).before(spinner);
    }

    function removeSpinner(target) {
        $(target).show();
        $(target).siblings(spinner).detach();
    }

    function disableButtons() {
        // var buttons = $('.button');
        var buttons = document.querySelectorAll('.button');

        for (var i = 0; i < buttons.length; ++i) {
            buttons[i].setAttribute("disabled", "disabled");
        }
    }

    function enableButtons() {
        var buttons = document.querySelectorAll('.button');

        for (var i = 0; i < buttons.length; ++i) {
            buttons[i].removeAttribute("disabled");
        }
    }

});
