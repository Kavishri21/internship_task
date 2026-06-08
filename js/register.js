$(document).ready(function() {
    const $form = $('#registerForm');
    const $submitBtn = $form.find('button[type="submit"]');
    const $alertContainer = $('#alert-container');
    const $alertMessage = $('#alert-message');

    function showAlert(type, message) {
        $alertContainer.removeClass('d-none');
        $alertMessage.removeClass('alert-danger alert-success');
        
        if (type === 'success') {
            $alertMessage.addClass('alert-success');
        } else {
            $alertMessage.addClass('alert-danger');
        }
        
        $alertMessage.text(message);
    }

    function hideAlert() {
        $alertContainer.addClass('d-none');
    }

    $form.on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        if (!name || !email || !password || !confirmPassword) {
            showAlert('error', 'Please fill in all form fields.');
            return;
        }

        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(email)) {
            showAlert('error', 'Please enter a valid email address.');
            return;
        }

        if (password.length < 8) {
            showAlert('error', 'Password must be at least 8 characters long.');
            return;
        }

        if (password !== confirmPassword) {
            showAlert('error', 'Passwords do not match. Please verify.');
            return;
        }

        $submitBtn.prop('disabled', true).text('Creating Account...');

        $.ajax({
            url: 'php/register.php',
            method: 'POST',
            data: {
                name: name,
                email: email,
                password: password,
                confirm_password: confirmPassword
            },
            dataType: 'json',
            success: function(response) {
                showAlert('success', response.message);
                $form[0].reset();
                
                setTimeout(function() {
                    window.location.href = 'login.html';
                }, 1500);
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                showAlert('error', errorMsg);
                $submitBtn.prop('disabled', false).text('Create Account');
            }
        });
    });
});
