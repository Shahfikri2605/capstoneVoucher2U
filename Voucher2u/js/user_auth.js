document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const messageDiv = document.getElementById('message');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const closeForgotPasswordModal = document.getElementById('closeForgotPasswordModal');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const forgotPasswordMessage = document.getElementById('forgotPasswordMessage');

    // Login form handler
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('../php/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (messageDiv) {
                    messageDiv.textContent = data.message;
                    messageDiv.style.color = data.success ? 'green' : 'red';
                }
                if (data.success) {
                    window.location.href = data.redirect; // Redirect to the specified page
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (messageDiv) {
                    messageDiv.textContent = 'An error occurred during login.';
                    messageDiv.style.color = 'red';
                }
            });
        });
    }

    // Forgot password modal handlers
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(event) {
            event.preventDefault();
            forgotPasswordModal.classList.add('active');
            // Clear any previous messages
            forgotPasswordMessage.innerHTML = '';
            forgotPasswordMessage.className = 'modal-message';
            // Focus on email input
            setTimeout(() => {
                const emailInput = forgotPasswordForm.querySelector('input[type="email"]');
                if (emailInput) emailInput.focus();
            }, 300);
        });
    }

    if (closeForgotPasswordModal) {
        closeForgotPasswordModal.addEventListener('click', function() {
            forgotPasswordModal.classList.remove('active');
        });
    }

    // Close modal when clicking outside
    if (forgotPasswordModal) {
        forgotPasswordModal.addEventListener('click', function(event) {
            if (event.target === forgotPasswordModal) {
                forgotPasswordModal.classList.remove('active');
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && forgotPasswordModal.classList.contains('active')) {
                forgotPasswordModal.classList.remove('active');
            }
        });
    }

    // Forgot password form handler
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('.modal-submit-btn');
            const originalBtnText = submitBtn.textContent;
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();

            // Basic client-side email validation
            if (!email) {
                showModalMessage('Please enter your email address.', 'error');
                return;
            }

            if (!isValidEmail(email)) {
                showModalMessage('Please enter a valid email address.', 'error');
                return;
            }

            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            fetch('../php/forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.using_mailhog && data.success) {
                    // MailHog is running - show success message with link to MailHog
                    showModalMessage(`
                        <div style="margin-bottom: 15px; text-align: center;">
                            <strong style="color: #28a745;">Email Sent Successfully!</strong>
                        </div>
                    `, 'success');
                } else if (data.demo_mode && data.reset_link && data.success) {
                    // MailHog not running - fallback to demo mode
                    showModalMessage(`
                        <div style="margin-bottom: 15px; text-align: center;">
                            <strong style="color: #ffc107;">Email sent error</strong>
                        </div>
                    `, 'success');
                } else if (data.success) {
                    // Standard success message
                    showModalMessage(data.message, 'success');
                } else {
                    // Error message (invalid email, etc.)
                    showModalMessage(data.message, 'error');
                }

                if (data.success) {
                    // Clear the form
                    forgotPasswordForm.reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModalMessage('A network error occurred. Please check your connection and try again.', 'error');
            })
            .finally(() => {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        });
    }

    // Helper function to show modal messages
    function showModalMessage(message, type) {
        if (forgotPasswordMessage) {
            forgotPasswordMessage.innerHTML = message;
            forgotPasswordMessage.className = `modal-message ${type}`;
        }
    }

    // Helper function to validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Google login handler
    window.handleGoogleLogin = async (response) => {
        const id_token = response.credential;

        try {
            const res = await fetch('../php/google_auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id_token: id_token, action: 'login' })
            });
            const data = await res.json();

            if (messageDiv) {
                messageDiv.textContent = data.message;
                messageDiv.style.color = data.success ? 'green' : 'red';
            }
            if (data.success) {
                window.location.href = data.redirect || 'HomePage.html';
            }
        } catch (error) {
            console.error('Error during Google login:', error);
            if (messageDiv) {
                messageDiv.textContent = 'An error occurred during Google login.';
                messageDiv.style.color = 'red';
            }
        }
    };
});