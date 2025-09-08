document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registrationForm');
    const messageDiv = document.getElementById('message');

    if (registrationForm) {
        registrationForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const password = document.querySelector('input[name="password"]').value;
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
});
