$(document).ready(function () {
    localStorage.setItem("emailsent", "no");
    $("#ConfirmModal").removeClass("d-none");
    var OnloadAccountID = $("input[name=account_id]:checked").val();
    checkoutText = $.trim($('#btnCompleteOrder').text());
    if (OnloadAccountID == "new") {
        $('#btnCompleteOrder').after("<button class='btn btn-primary btn-lg' id='confirmemaildiv' type='button' onclick='ButtonTrick()'>" + checkoutText + " <i class='fas fa-arrow-circle-right'></i></button>");
        $('#btnCompleteOrder').remove();
    }
    if (OnloadAccountID != "new") {
        $('#confirmemaildiv').after('<button type="submit" id="btnCompleteOrder" class="btn btn-primary btn-lg disable-on-click spinner-on-click">' + checkoutText + '&nbsp;<i class="fas fa-arrow-circle-right"></i></button>');
        $('#confirmemaildiv').remove();
    }
    $('input').on('ifChecked', function (event) {
        if ($(this).attr("name") == "account_id" && $(this).attr("value") == "new") {
            $('#btnCompleteOrder').after("<button class='btn btn-primary btn-lg' id='confirmemaildiv' type='button' onclick='ButtonTrick()'>" + checkoutText + " <i class='fas fa-arrow-circle-right'></i></button>");
            $('#btnCompleteOrder').remove();
        }
        if ($(this).attr("name") == "account_id" && $(this).attr("value") != "new") {
            $('#confirmemaildiv').after('<button type="submit" id="btnCompleteOrder" class="btn btn-primary btn-lg disable-on-click spinner-on-click">' + checkoutText + '&nbsp;<i class="fas fa-arrow-circle-right"></i></button>');
            $('#confirmemaildiv').remove();
        }
    });

    $("#sendemail").click(function () {
        $(this).text('Sending Email');
        $(".addborder").removeClass("addborder");
        EmailValue = $("input[name='email']").val();
        if (IsEmailValid(EmailValue) && EmailValue != "") {
            $(this).prop("disabled", true);
            jQuery.ajax({
                type: "POST",
                url: "modules/addons/ValidEmail/ajaxhandler.php",
                dataType: "text",
                data: {
                    action: 'sendvalidcode',
                    email: EmailValue
                },
                success: function (response2) {
                    $(this).prop("disabled", false);
                    var obj = jQuery.parseJSON(response2);
                    if (obj.result == "success") {
                        if (obj.response != "emailconfirmer") {
                            localStorage.setItem("emailsent", "yes");
                            $("#customBodyModal").html('<div class="alert alert-info" style="display: block;"><strong>Success!</strong> The Code has been sent to your email address. Kindly check your junk mailbox or spam folders as well if you are not able to find the email.</div>');
                            $("#validatecode").show();
                            $(".checkValidCode").show();
                            $("#ConfirmModal").modal("show");
                            var timeleft = 120;
                            var downloadTimer = setInterval(function () {
                                if (timeleft <= 0) {
                                    clearInterval(downloadTimer);
                                    $("#sendemail").text('Resend Verification Code');
                                    $("#sendemail").prop('disabled', false);

                                    jQuery.ajax({
                                        type: "POST",
                                        url: "modules/addons/ValidEmail/ajaxhandler.php",
                                        dataType: "text",
                                        data: {
                                            action: 'expirecode',
                                            email: EmailValue
                                        },
                                        success: function (response2) {
                                        }
                                    });

                                } else {
                                    $("#sendemail").text('Resend Verification Code (' + timeleft + ')');
                                }
                                timeleft -= 1;
                            }, 1000);
                        }
                    } else {
                        localStorage.setItem("emailsent", "no");
                        alert(obj.response);
                    }
                }
            });
        }
    });

    //Validate the code
    $(".checkValidCode ").click(function (e) {
        e.preventDefault();
        thisval = $(this);
        $(".addborder").removeClass("addborder");
        validatecode = $("#validatecode").val();
        if (validatecode != "") {
            thisval.prop("disabled", true);
            thisval.text("Progressing");
            jQuery.ajax({
                type: "POST",
                url: "modules/addons/ValidEmail/ajaxhandler.php",
                dataType: "text",
                data: {
                    action: 'validatecode',
                    email: EmailValue,
                    validatecode: validatecode
                },
                success: function (response2) {
                    if (response2 == '1') {
                        $("#sendemail").remove();
                        thisval.text("Validated");
                        $("#confirmModalBody").html("<div class='alert alert-success' role='alert'>Code Validated Successfully.<br><b>You will be redirected in <span id='timerleft'>3</span> seconds.</b></div>");
                        var timeleft = 3;
                        var downloadTimer = setInterval(function () {
                            if (timeleft <= 0) {
                                clearInterval(downloadTimer);
                                $("#ConfirmModal").modal("hide");
                                $('#confirmemaildiv').after('<button type="submit" id="btnCompleteOrder" class="btn btn-primary btn-lg disable-on-click spinner-on-click">' + checkoutText + '&nbsp;<i class="fas fa-arrow-circle-right"></i></button>');
                                $('#confirmemaildiv').remove();
                                $("#btnCompleteOrder").trigger("click");

                            } else {
                                $("#timerleft").text(timeleft);
                            }
                            timeleft -= 1;
                        }, 1000);
                    } else {
                        $("#confirmModalBody").html("<div class='alert alert-danger' role='alert'>Invalid or Expired Code</div><input type='text' id='validatecode' placeholder='Enter Code Here' class='form-control'>");
                        $(document).find('.checkValidCode').prop("disabled", false);
                        $(document).find('.checkValidCode').text('Validate');
                    }
                }
            });
        } else {
            $("#validatecode").addClass("addborder");
        }
    });
});

function IsEmailValid(email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test(email);
}

function ButtonTrick() {
    $("#confirmemaildiv").prop("disabled", true);
    EmailValue = $("input[name='email']").val();
    jQuery.ajax({
        type: "POST",
        url: "modules/addons/ValidEmail/ajaxhandler.php",
        dataType: "text",
        data: {
            action: 'checkuser',
            email: EmailValue
        },
        success: function (response2) {
            $("#confirmemaildiv").prop("disabled", false);
            var obj = jQuery.parseJSON(response2);
            if (obj.result == "success") {
                if (obj.response == "yes") {
                    $('#confirmemaildiv').after('<button type="submit" id="btnCompleteOrder" class="btn btn-primary btn-lg disable-on-click spinner-on-click">' + checkoutText + '&nbsp;<i class="fas fa-arrow-circle-right"></i></button>');
                    $('#confirmemaildiv').remove();
                    $("#btnCompleteOrder").trigger("click");
                } else {
                    emailsent = localStorage.getItem("emailsent");
                    $(".addborder").removeClass("addborder");
                    if (IsEmailValid(EmailValue) && EmailValue != "") {
                        if (emailsent == "yes") {
                            $("#ConfirmModal").modal("show");
                        } else {
                            $("#ConfirmModal").modal("show");
                            $("#customBodyModal").html('<p style="text-overflow: ellipsis;white-space: nowrap;overflow: hidden;font-size: 15px;">Before continuing, You must verify your email address <b>' + EmailValue + '</b></p>');
                        }
                    } else {
                        $("input[name='email']").addClass("addborder");
                        alert('Please enter your email address!');
                    }
                }
            } else {
                localStorage.setItem("emailsent", "no");
                alert(obj.response);
            }
        }
    });
}