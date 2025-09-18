document.addEventListener('DOMContentLoaded', () => {
    const authLink = document.getElementById('auth-link');
    const userPointsSpan = document.getElementById('user-points'); // Get the points display element
    const logoutLink = document.getElementById('logout-link'); // Get the logout link
    const profileDropdownContainer = document.querySelector('.profile-dropdown-container');

    // Function to fetch and display user points
    async function fetchAndDisplayUserPoints() {
        const userId = localStorage.getItem('userId');
        if (userId && userPointsSpan) {
            try {
                const response = await fetch(`../php/fetch_user_points.php?user_id=${userId}`);
                const data = await response.json();

                if (data.success) {
                    userPointsSpan.textContent = `${data.points} Points`;
                    localStorage.setItem('userPoints', data.points); // Store user points in localStorage
                    window.dispatchEvent(new Event('userPointsUpdated')); // Dispatch event after points are updated
                } else {
                    console.error('Error fetching user points:', data.message);
                    userPointsSpan.textContent = 'N/A Points';
                    localStorage.removeItem('userPoints'); // Clear points from localStorage on error
                    window.dispatchEvent(new Event('userPointsUpdated')); // Dispatch event after points are cleared
                }
            } catch (error) {
                console.error('Network error or failed to parse JSON for user points:', error);
                userPointsSpan.textContent = 'Error';
                localStorage.removeItem('userPoints'); // Clear points from localStorage on error
                window.dispatchEvent(new Event('userPointsUpdated')); // Dispatch event after points are cleared
            }
        } else if (userPointsSpan) {
            userPointsSpan.textContent = ''; // Clear points if not logged in
            localStorage.removeItem('userPoints'); // Clear points from localStorage
            window.dispatchEvent(new Event('userPointsUpdated')); // Dispatch event after points are cleared
        }
    }

    // Function to handle logout
    async function handleLogout() {
        try {
            const response = await fetch('../php/logout.php', {
                method: 'POST' // Using POST for logout for better practice
            });
            const data = await response.json();

            if (data.success) {
                localStorage.removeItem('userId'); // Clear userId from localStorage
                localStorage.removeItem('userPoints'); // Clear userPoints from localStorage on logout
                window.location.href = 'LoginPage.html'; // Redirect to login page
            } else {
                console.error('Logout failed:', data.message);
                alert('Logout failed: ' + data.message);
            }
        } catch (error) {
            console.error('Network error or failed to parse JSON during logout:', error);
            alert('An error occurred during logout.');
        }
    }

    fetch('../php/check_login.php')
        .then(response => response.json())
        .then(data => {
            if (data.loggedIn) {
                authLink.innerHTML = `<i class="fas fa-user-circle"></i> ${data.userName}`;
                authLink.href = "#"; // Keep it as # since dropdown handles navigation
                if (profileDropdownContainer) {
                    profileDropdownContainer.style.display = 'inline-block'; // Show dropdown container
                }
                localStorage.setItem('userId', data.userId); // Store userId in localStorage
                fetchAndDisplayUserPoints(); // Fetch and display points after login check
            } else {
                authLink.innerHTML = `<i class="fas fa-user"></i> Login`;
                authLink.href = "LoginPage.html";
                if (profileDropdownContainer) {
                    profileDropdownContainer.style.display = 'inline-block'; // Keep it inline-block even if not logged in
                    const dropdownMenu = profileDropdownContainer.querySelector('.dropdown-menu-profile');
                    if (dropdownMenu) {
                        dropdownMenu.style.display = 'none'; // Hide dropdown menu if not logged in
                    }
                }
                localStorage.removeItem('userId'); // Remove userId if not logged in
                localStorage.removeItem('userPoints'); // Clear userPoints from localStorage if not logged in
                if (userPointsSpan) userPointsSpan.textContent = ''; // Clear points
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            authLink.innerHTML = `<i class="fas fa-user"></i> Login`;
            authLink.href = "LoginPage.html";
            if (profileDropdownContainer) {
                profileDropdownContainer.style.display = 'inline-block';
                const dropdownMenu = profileDropdownContainer.querySelector('.dropdown-menu-profile');
                if (dropdownMenu) {
                    dropdownMenu.style.display = 'none';
                }
            }
            localStorage.removeItem('userPoints'); // Clear userPoints from localStorage on login check error
            if (userPointsSpan) userPointsSpan.textContent = '';
        });

    // Add event listener for logout link
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default link behavior
            handleLogout();
        });
    }
});
