document.addEventListener('DOMContentLoaded', () => {
    const authLink = document.getElementById('auth-link');

    fetch('../php/check_login.php')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                authLink.innerHTML = `<i class="fas fa-user-circle"></i> ${data.userName}`;
<<<<<<< HEAD
                authLink.href = "ProfilePage.html"; // Or a profile page link
=======
                authLink.href = "#"; // Or a profile page link
                localStorage.setItem('userId', data.userId); // Store userId in localStorage
>>>>>>> ceaf66459517dd151214c48cb95f83e265937147
            } else {
                authLink.innerHTML = `<i class="fas fa-user"></i> Login`;
                authLink.href = "LoginPage.html";
                localStorage.removeItem('userId'); // Remove userId if not logged in
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            authLink.innerHTML = `<i class="fas fa-user"></i> Login`;
            authLink.href = "LoginPage.html";
        });
});
