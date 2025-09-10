document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registrationForm');
    const messageDiv = document.getElementById('message');

    if (registrationForm) {
        registrationForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const password = document.querySelector('input[name="Password"]').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                if (messageDiv) {
                    messageDiv.textContent = 'Passwords do not match.';
                    messageDiv.style.color = 'red';
                }
                return;
            }

            const formData = new FormData(this);

            fetch('../php/register.php', {
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
                    // Optionally redirect or clear form
                    // window.location.href = 'success.html';
                    this.reset(); // Clear the form
                    alert('Registration successful! You can now log in.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (messageDiv) {
                    messageDiv.textContent = 'An error occurred during registration.';
                    messageDiv.style.color = 'red';
                }
            });
        });
    }

    window.handleGoogleRegister = async (response) => {
        const id_token = response.credential;

        try {
            const res = await fetch('../php/google_auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id_token: id_token, action: 'register' })
            });
            const data = await res.json();

            if (messageDiv) {
                messageDiv.textContent = data.message;
                messageDiv.style.color = data.success ? 'green' : 'red';
            }
            if (data.success) {
                alert('Google registration successful! You can now log in.');
                window.location.href = data.redirect || 'HomePage.html';
            }
        } catch (error) {
            console.error('Error during Google registration:', error);
            if (messageDiv) {
                messageDiv.textContent = 'An error occurred during Google registration.';
                messageDiv.style.color = 'red';
            }
        }
    };
});
