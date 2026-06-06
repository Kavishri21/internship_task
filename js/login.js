// js/login.js
// Client-side login handler using jQuery AJAX

$(document).ready(function() {
    const $form = $('#loginForm');
    const $submitBtn = $form.find('button[type="submit"]');
    const $alertContainer = $('#alert-container');
    const $alertMessage = $('#alert-message');

    // Helper to display alert message
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

    // Helper to hide alert
    function hideAlert() {
        $alertContainer.addClass('d-none');
    }

    $form.on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        // 1. Gather form input values
        const email = $('#email').val().trim();
        const password = $('#password').val();

        // 2. Perform frontend validations
        if (!email || !password) {
            showAlert('error', 'Please fill in all form fields.');
            return;
        }

        // Email regex check
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(email)) {
            showAlert('error', 'Please enter a valid email address.');
            return;
        }

        // 3. Prevent double submission: disable button and update text
        $submitBtn.prop('disabled', true).text('Signing In...');

        // 4. Send jQuery AJAX request
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
                
                // Store the secure session token in localStorage
                localStorage.setItem('session_token', response.session_token);
                
                // Reset form inputs
                $form[0].reset();
                
                // Redirect user to profile dashboard after 1 second
                setTimeout(function() {
                    window.location.href = 'profile.html';
                }, 1000);
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred during login. Please try again.';
                
                // Read response error message if available
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                showAlert('error', errorMsg);
                
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text('Sign In');
            }
        });
    });
});
