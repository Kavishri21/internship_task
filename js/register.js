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
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                </svg>
            `);
        } else {
            $btn.html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a8 8 0 0 0-2.79.513L9.066 5.892a3 3 0 0 1 4.149 4.149zm-1.915-1.907-.817-.817A3 3 0 0 0 7.47 5.348l-.816-.816A15 15 0 0 1 8 3.5c5 0 8 5.5 8 5.5a12 12 0 0 1-2.73 3.494zM11 8a3 3 0 0 0-3-3L11 8z"/>
                    <path d="M7.4 4.4a3 3 0 0 0-3 3L7.4 4.4zm.8 4.4a3 3 0 0 0-3-3L8.2 8.8zm-4.3 2.5c-1.5-.7-3-2.5-3-2.5s3-5.5 8-5.5c.9 0 1.8.2 2.6.5l-.8.8a6.7 6.7 0 0 0-1.8-.3c-5 0-8 5.5-8 5.5a12 12 0 0 0 2.2 3.1z"/>
                    <path d="M1 1l14 14"/>
                </svg>
            `);
        }
    });
});
