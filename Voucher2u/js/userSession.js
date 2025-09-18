document.addEventListener('DOMContentLoaded', () => {
    const authLink = document.getElementById('auth-link');

    fetch('../php/check_login.php')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                authLink.innerHTML = `<i class="fas fa-user-circle"></i> ${data.userName}`;
                authLink.href = "ProfilePage.html"; // Or a profile page link
            } else {
                authLink.innerHTML = `<i class="fas fa-user"></i> Login`;
                authLink.href = "LoginPage.html";
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            authLink.innerHTML = `<i class="fas fa-user"></i> Login`;
            authLink.href = "LoginPage.html";
        });
});
