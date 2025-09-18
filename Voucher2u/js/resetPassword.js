document.addEventListener('DOMContentLoaded', function() {
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const messageDiv = document.getElementById('message');
    const resetBtn = document.getElementById('resetBtn');

    // Get token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    // Check if token exists
    if (!token) {
        showMessage('Invalid or missing reset token. Please request a new password reset.', 'error');
        resetBtn.disabled = true;
        
        // Redirect to login after 5 seconds
        setTimeout(() => {
            window.location.href = 'LoginPage.html';
        }, 5000);
        return;
    }

    // Show message function
    function showMessage(text, type) {
        messageDiv.innerHTML = text;
        messageDiv.className = type;
        messageDiv.style.display = 'block';
    }

    // Hide message function
    function hideMessage() {
        messageDiv.style.display = 'none';
        messageDiv.className = '';
    }

    // Password validation function (simplified)
    function validatePasswords() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Clear any previous messages
        hideMessage();

        // Check if passwords match
        if (newPassword !== confirmPassword) {
            showMessage('New passwords do not match.', 'error');
            return false;
        }

        // Check password length (minimum 8 characters)
        if (newPassword.length < 8) {
            showMessage('New password must be at least 8 characters long.', 'error');
            return false;
        }

        return true;
    }

    // Real-time validation as user types
    newPasswordInput.addEventListener('input', function() {
        if (confirmPasswordInput.value && newPasswordInput.value) {
            // Only validate if both fields have content
            validatePasswords();
        } else {
            hideMessage();
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        if (newPasswordInput.value && confirmPasswordInput.value) {
            // Only validate if both fields have content
            validatePasswords();
        } else {
            hideMessage();
        }
    });

    // Form submission
    resetPasswordForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Basic validation
        if (!newPassword || !confirmPassword) {
            showMessage('Please fill in both password fields.', 'error');
            return;
        }

        // Validate passwords using our simplified validation
        if (!validatePasswords()) {
            return; // Error message already shown by validatePasswords()
        }

        // Prepare form data
        const formData = new FormData();
        formData.append('token', token);
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);

        // Disable submit button and show loading state
        resetBtn.disabled = true;
        resetBtn.textContent = 'Resetting Password...';
        resetBtn.classList.add('loading');

        // Send request
        fetch('../php/reset_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Password reset successful! Redirecting to login page...', 'success');
                
                // Clear form
                resetPasswordForm.reset();

                // Redirect to login after 3 seconds
                let countdown = 3;
                const countdownInterval = setInterval(() => {
                    showMessage(`Password reset successful! Redirecting to login in ${countdown} seconds...`, 'success');
                    countdown--;
                    
                    if (countdown < 0) {
                        clearInterval(countdownInterval);
                        window.location.href = 'LoginPage.html';
                    }
                }, 1000);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            // Re-enable button and remove loading state
            resetBtn.disabled = false;
            resetBtn.textContent = 'Reset Password';
            resetBtn.classList.remove('loading');
        });
    });

    // Focus on first input when page loads
    setTimeout(() => {
        newPasswordInput.focus();
    }, 300);
});