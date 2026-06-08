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

    $('.toggle-password').on('click', function() {
        const $btn = $(this);
        const $input = $($btn.attr('data-target'));
        const type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        
        if (type === 'password') {
            $btn.html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                    <path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                    <line x1="2" x2="22" y1="2" y2="22"/>
                </svg>
            `);
        } else {
            $btn.html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            `);
        }
    });
});
