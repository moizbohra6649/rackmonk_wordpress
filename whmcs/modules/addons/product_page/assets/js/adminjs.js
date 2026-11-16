var tinymceSettings = {
    selector: "textarea.tinymce",
    height: 500,
    theme: "modern",
    entity_encoding: "raw",
    plugins: "autosave print preview searchreplace autolink directionality visualblocks visualchars fullscreen image link media template code codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists textcolor wordcount contextmenu colorpicker textpattern help paste",
    toolbar: [
        "formatselect,fontselect,fontsizeselect,|,bold,italic,strikethrough,underline,forecolor,backcolor,|,link,unlink,|,justifyleft,justifycenter,justifyright,justifyfull,|,search,replace,|,bullist,numlist,",
        "outdent,indent,blockquote,|,undo,redo,|,cut,copy,paste,pastetext,pasteword,|,table,|,hr,|,sub,sup,|,charmap,image,media,|,print,|,ltr,rtl,|,fullscreen,|,help,code,removeformat"
    ],
    image_advtab: true,
    content_css: [
        "//fonts.googleapis.com/css?family=Lato:300,300i,400,400i",
        "//www.tinymce.com/css/codepen.min.css"
    ],
    browser_spellcheck: true,
    convert_urls: false,
    relative_urls: false,
    forced_root_block: "p",
    media_poster: false,
    mobile: {
        theme: "mobile",
        plugins: ["autosave", "lists", "autolink"],
        toolbar: ["undo", "bold", "italic", "styleselect"]
    },
    menu: {
        file: {
            title: "File",
            items: "preview | print"
        },
        edit: {
            title: "Edit",
            items: "undo redo | cut copy paste pastetext | selectall | searchreplace"
        },
        view: {
            title: "View",
            items: "visualaid visualchars visualblocks | preview fullscreen"
        },
        insert: {
            title: "Insert",
            items: "image anchor link media codesample | charmap hr"
        },
        format: {
            title: "Format",
            items: "bold italic strikethrough underline superscript subscript codeformat | blockformats align | removeformat"
        },
        table: {
            title: "Table",
            items: "inserttable tableprops deletetable | cell row column"
        },
        help: {
            title: "Help",
            items: "help | code"
        }
    },


};

$(document).ready(function () {
    tinymce.init(tinymceSettings).then(function (editors) {
        editorLoaded = true;
        tinymce.addI18n('image', {
            "Image list": 'Recent images'
        });
    });

    
});

var editorEnabled = true,
    editorLoaded = false;

function toggleEditor() {
    if (editorEnabled === true) {
        tinymce.activeEditor.remove();
        editorEnabled = false;
    } else {
        tinymce.init(tinymceSettings);
        editorEnabled = true;
    }
}

function insertMergeField(mfield) {
    tinymce.activeEditor.insertContent('{$' + mfield + '}');
}

$(document).ready(function () {
    $('#deletelogo').click(function () {
        swal({
            title: "Are you sure you want delete?",
            icon: "warning",
            buttons: true, // This enables both "OK" and "Cancel" buttons
            dangerMode: true, // Optional, makes "OK" button appear in red
        })
            .then((willConfirm) => {
                if (willConfirm) {
                    $("#deletelogoform").submit();
                }
            });
    });
    $('.deletescreenshot').click(function () {
        var screenshotName = $(this).data("screenshotname");
        var screenshotid = $(this).data("screenshotid");
        swal({
            title: "Are you sure you want delete?",
            icon: "warning",
            buttons: true, // This enables both "OK" and "Cancel" buttons
            dangerMode: true, // Optional, makes "OK" button appear in red
        })
            .then((willConfirm) => {
                if (willConfirm) {
                    $("#sliderimagename").val(screenshotName);
                    $("#sliderimageid").val(screenshotid);
                    $("#sliderimage").submit();
                }
            });
    })
    $('.addbtnbtn').click(function () {
        var totalcountbtn = $('.addbtnarraycount').val();
        var increment = Number(totalcountbtn) + 1;
        console.log(increment);
        $('.btncontentadd').append('<div class="row"><div class="col-md-2">Label<span style="color:red">*</span> <input type="text" name="addbtn[' + totalcountbtn + '][btntext]" class="form-control" required></div><div class="col-md-3">Link<span style="color:red">*</span> <input type="text" name="addbtn[' + totalcountbtn + '][btnlink]" class="form-control" required></div><div class="col-md-2">Color&nbsp;<a href="https://getbootstrap.com/docs/4.0/components/buttons/" target="_blank"><i class="fa fa-search-plus" aria-hidden="true"></i></a> <select name="addbtn[' + totalcountbtn + '][color]" class="form-control"><option value="primary">primary</option><option value="secondary">secondary</option><option value="success">success</option><option value="danger">danger</option><option value="warning">warning</option><option value="info">info</option><option value="light">light</option><option value="dark">dark</option></select></div><div class="col-md-2">Open In <select name="addbtn[' + totalcountbtn + '][window]" class="form-control"><option value="_self">Same Window</option><option value="_blank">New Window</option></select></div><div class="col-md-2">Fa Icon Class&nbsp;<a href="https://fontawesome.com/v4/icons/" target="_blank"><i class="fa fa-search-plus" aria-hidden="true"></i></a> <input type="text" name="addbtn[' + totalcountbtn + '][btnicon]" class="form-control" value=""></div><div class="col-md-1"><center><button type="button" class="btn btn-danger" onclick="removebuttonbtnntsave(this);" style="margin-top:9px;"><i class="fa fa-trash" aria-hidden="true"></i></button></center></div>');
        $('.addbtnarraycount').val(increment);
    })

    $('.removebuttonbtn').click(function () {
        var removeidbtn = $(this).data('buttonkey');
        swal({
            title: "Are you sure you want delete?",
            icon: "warning",
            buttons: true, // This enables both "OK" and "Cancel" buttons
            dangerMode: true, // Optional, makes "OK" button appear in red
        })
            .then((willConfirm) => {
                if (willConfirm) {
                    $('.removeclass' + removeidbtn).remove();
                    // $('#saveChanges').click();

                }
            });
    });


    let isFormDirty = false;

    // Mark form as dirty on any input change
    $('#mainform').on('input', ':input', function () {
        isFormDirty = true;
    });

    // Warn the user before leaving the page
    $(window).on('beforeunload', function (event) {
        if (isFormDirty) {
            // Show confirmation dialog
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    // Reset the dirty flag on form submission
    $('#mainform').on('submit', function () {
        isFormDirty = false;
    });

    var selectedValue = $("input[name='logochoice']:checked").val();
    if (selectedValue == "url") {
        $('.uploadlogocontent').hide();
    } else {
        $('.logourlcontent').hide();
    }

    $("input[name='logochoice']").change(function () {
        var selectedValuechange = $("input[name='logochoice']:checked").val();
        if (selectedValuechange == "url") {
            $('.logourlcontent').show();
            $('.uploadlogocontent').hide();
        } else {
            $('.uploadlogocontent').show();
            $('.logourlcontent').hide();

        }
    });
})

function removebuttonbtnntsave(button) {
    swal({
        title: "Are you sure you want delete?",
        icon: "warning",
        buttons: true, // This enables both "OK" and "Cancel" buttons
        dangerMode: true, // Optional, makes "OK" button appear in red
    })
        .then((willConfirm) => {
            if (willConfirm) {
                $(button).parent().closest('.row').remove();
            }
        });
}
