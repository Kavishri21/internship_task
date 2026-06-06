// js/register.js
// Client-side registration handler using jQuery AJAX

$(document).ready(function() {
    const $form = $('#registerForm');
    const $submitBtn = $form.find('button[type="submit"]');
    const $alertContainer = $('#alert-container');
    const $alertMessage = $('#alert-message');

    // Helper to display alert box messages
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

    // Helper to hide alert box
    function hideAlert() {
        $alertContainer.addClass('d-none');
    }

    $form.on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        // 1. Gather form input values
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        // 2. Perform frontend validations
        if (!name || !email || !password || !confirmPassword) {
            showAlert('error', 'Please fill in all form fields.');
            return;
        }

        // Email regex check
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(email)) {
            showAlert('error', 'Please enter a valid email address.');
            return;
        }

        // Password strength (at least 8 chars)
        if (password.length < 8) {
            showAlert('error', 'Password must be at least 8 characters long.');
            return;
        }

        // Password match check
        if (password !== confirmPassword) {
            showAlert('error', 'Passwords do not match. Please verify.');
            return;
        }

        // 3. Prevent double submission: disable button and update text
        $submitBtn.prop('disabled', true).text('Creating Account...');

        // 4. Send jQuery AJAX request
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
                
                // Reset form inputs
                $form[0].reset();
                
                // Redirect user to login screen after 1.5 seconds
                setTimeout(function() {
                    window.location.href = 'login.html';
                }, 1500);
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred. Please try again.';
                
                // Read response error message if available
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                showAlert('error', errorMsg);
                
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text('Create Account');
            }
        });
    });
});
