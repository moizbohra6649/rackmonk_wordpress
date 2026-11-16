 var checkboxState = {};

        function toggleField(field, show) {
            var checkboxLabel = document.getElementById(field + 'CheckboxLabel');
            var checkbox = document.getElementById(field + 'Checkbox');
            var showRadio = document.getElementById('show_' + field);
            var hideRadio = document.getElementById('hide_' + field);

            // Handle visibility and checkbox state for non-companyname fields
            if (show) {
                // checkboxLabel.style.display = "block";
                // make checkbox remove disabled
                checkbox.removeAttribute('disabled');
                // check the checkbox
                
                showRadio.checked = true;
                hideRadio.checked = false;

                // Restore the checkbox state when switching back to "show"
                if (checkboxState[field] !== undefined) {
                    checkbox.checked = checkboxState[field]; // Retain the checkbox state
                } else {
                    if (field == 'company') {
                        checkbox.checked = true;
                    }
                }
            } else {
                // checkboxLabel.style.display = "none";
                checkbox.setAttribute('disabled', 'disabled');
                checkbox.checked = true
                showRadio.checked = false;
                hideRadio.checked = true;
            }
            updateField(field);
        }

        function updateField(fieldName) {
            // Get the checkbox state and store it in the backend (converted to 1 or 0)
            var checkboxStateValue = checkboxState[fieldName] === true ? 1 : 0;
            var showValue = document.querySelector('input[name="' + fieldName + 'Visibility"]:checked');
            var show = showValue ? showValue.value : 'show';
        }

function submitForm() {
            $('.submitbtn i').removeClass('hidden');
            // ajax call to submit the form
            $.ajax({
                type: 'POST',
                url: 'addonmodules.php?module=ClientsFieldManager&action=savedata',
                data: $('#fieldForm').serialize(),
                success: function(response) {
                    // swal fire with ok button and refresh page
                    $('.submitbtn i').addClass('hidden');
                    Swal.fire({
                        title: 'Success!',
                        text: 'Changes saved successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        location.reload();
                    });
                },
                error: function (response) {
                    $('.submitbtn i').addClass('hidden');
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to save changes.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }