document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const messageDiv = document.getElementById('message');

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
