$(document).ready(function() {
    const $form = $('#loginForm');
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

        const email = $('#email').val().trim();
        const password = $('#password').val();

        if (!email || !password) {
            showAlert('error', 'Please fill in all form fields.');
            return;
        }

        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(email)) {
            showAlert('error', 'Please enter a valid email address.');
            return;
        }

        $submitBtn.prop('disabled', true).text('Signing In...');

        $.ajax({
            url: 'php/login.php',
            method: 'POST',
            data: {
                email: email,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                showAlert('success', response.message);
                localStorage.setItem('session_token', response.session_token);
                $form[0].reset();
                
                setTimeout(function() {
                    window.location.href = 'profile.html';
                }, 1000);
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred during login. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                showAlert('error', errorMsg);
                $submitBtn.prop('disabled', false).text('Sign In');
            }
        });
    });
});
