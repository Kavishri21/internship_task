// js/profile.js
// Client-side profile dashboard coordinator utilizing jQuery AJAX

$(document).ready(function() {
    const token = localStorage.getItem('session_token');

    // If no token exists on load, push to login immediately
    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    const $form = $('#profileForm');
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

    // --- 1. Load User Profile Details ---
    function loadProfile() {
        $.ajax({
            url: 'php/profile.php',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const data = response.data;

                    // Bind user header attributes
                    $('#navbar-user-email').text(data.email);
                    $('#welcome-name').text(data.name);

                    // Bind overview details text
                    $('#profile-name').text(data.name);
                    $('#profile-email').text(data.email);

                    // Handle age binding
                    if (data.age) {
                        $('#profile-age').text(data.age);
                        $('#age').val(data.age);
                    } else {
                        $('#profile-age').text('Not set');
                        $('#age').val('');
                    }

                    // Handle date of birth binding
                    if (data.dob) {
                        $('#profile-dob').text(data.dob);
                        $('#dob').val(data.dob);
                    } else {
                        $('#profile-dob').text('Not set');
                        $('#dob').val('');
                    }

                    // Handle contact binding
                    if (data.contact) {
                        $('#profile-contact').text(data.contact);
                        $('#contact').val(data.contact);
                    } else {
                        $('#profile-contact').text('Not set');
                        $('#contact').val('');
                    }
                }
            },
            error: function(xhr) {
                // If unauthorized (401), session is expired or deleted; kick to login
                if (xhr.status === 401) {
                    localStorage.removeItem('session_token');
                    window.location.href = 'login.html';
                } else {
                    showAlert('error', 'Could not load profile details. Please try again.');
                }
            }
        });
    }

    // Run initial load
    loadProfile();

    // --- 2. Profile Details Form Submission ---
    $form.on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        const age = parseInt($('#age').val(), 10);
        const dob = $('#dob').val();
        const contact = $('#contact').val().trim();

        // Client validation
        if (isNaN(age) || age < 1 || age > 120) {
            showAlert('error', 'Please enter a valid age (1-120).');
            return;
        }

        if (!dob) {
            showAlert('error', 'Please enter your date of birth.');
            return;
        }

        if (!contact || contact.length < 7 || contact.length > 20) {
            showAlert('error', 'Please enter a valid contact number (7-20 characters).');
            return;
        }

        // Prevent double submit
        $submitBtn.prop('disabled', true).text('Saving Profile Details...');

        $.ajax({
            url: 'php/profile.php',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            data: {
                age: age,
                dob: dob,
                contact: contact
            },
            dataType: 'json',
            success: function(response) {
                showAlert('success', response.message);
                $submitBtn.prop('disabled', false).text('Save Profile Details');
                
                // Refresh table details after saving
                loadProfile();
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    localStorage.removeItem('session_token');
                    window.location.href = 'login.html';
                    return;
                }
                
                let errorMsg = 'Could not save profile details. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                showAlert('error', errorMsg);
                $submitBtn.prop('disabled', false).text('Save Profile Details');
            }
        });
    });

    // --- 3. Logout Request Action ---
    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        
        // Clear bearer token
        localStorage.removeItem('session_token');
        
        // Push user to sign in
        window.location.href = 'login.html';
    });
});
