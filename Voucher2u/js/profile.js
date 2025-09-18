document.addEventListener('DOMContentLoaded', async () => {
    const profileName = document.getElementById('profile-name');
    const profileEmail = document.getElementById('profile-email');
    const phoneNumber = document.getElementById('phone-number');
    const address = document.getElementById('address');
    const loyaltyPointsBalance = document.getElementById('loyalty-points-balance');
    const paymentHistoryTableBody = document.querySelector('.payment-table tbody');
    const totalPaymentCount = document.querySelector('.payment-filters .total-count');
    const currentTier = document.querySelector('.loyalty-progress .current-tier');
    const progressBar = document.querySelector('.loyalty-progress .progress-bar');
    const pointsToNextTier = document.querySelector('.loyalty-progress p:last-of-type');
    const activitiesContainer = document.querySelector('.activities');

    const userId = localStorage.getItem('userId');

    if (!userId) {
        alert('Please log in to view your profile.');
        window.location.href = 'LoginPage.html';
        return;
    }

    try {
        const response = await fetch(`../php/fetch_user_profile.php?user_id=${userId}`);
        const data = await response.json();

        if (data.success && data.profile) {
            const profile = data.profile;

            // Populate General Information
            if (profileName) profileName.textContent = profile.name;
            if (profileEmail) profileEmail.textContent = profile.email;

            if (phoneNumber) phoneNumber.textContent = profile.phone;
            if (address) address.textContent = profile.address;

            // Populate Loyalty Points Balance
            if (loyaltyPointsBalance) loyaltyPointsBalance.textContent = profile.loyalty_points_balance;

            // Populate Payment History
            if (paymentHistoryTableBody) {
                paymentHistoryTableBody.innerHTML = ''; // Clear existing placeholder rows
                profile.payment_history.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.voucher}</td>
                        <td>${item.amount}</td>
                        <td class="status ${item.status.toLowerCase()}">${item.status}</td>
                        <td>${item.time}</td>
                        <td>${item.date}</td>
                        <td>${item.device}</td>
                        <td>${item.ip_address}</td>
                        <td><i class="fas fa-ellipsis-h"></i></td>
                    `;
                    paymentHistoryTableBody.appendChild(row);
                });
            }
            if (totalPaymentCount) totalPaymentCount.textContent = `${profile.payment_history_total} total`;

            // Populate Loyalty Program Progress
            if (currentTier) currentTier.textContent = profile.loyalty_progress.current_tier;
            if (progressBar) progressBar.style.width = `${profile.loyalty_progress.progress_percent}%`;
            if (pointsToNextTier) pointsToNextTier.textContent = `You need ${profile.loyalty_progress.points_to_next_tier} more points to reach ${profile.loyalty_progress.next_tier}.`;

            // Populate Activities
            if (activitiesContainer) {
                // Clear existing activities except the header (h3) and show more button
                const existingActivities = activitiesContainer.querySelectorAll('.activity-item');
                existingActivities.forEach(item => item.remove());

                const showMoreBtn = activitiesContainer.querySelector('.show-more-btn');

                profile.activities.forEach(activity => {
                    const activityItem = document.createElement('div');
                    activityItem.classList.add('activity-item');
                    activityItem.innerHTML = `
                        <p>${activity.description}</p>
                        <span>${activity.timestamp}</span>
                        <a href="#">Show detail</a>
                    `;
                    // Insert before the "Show more" button if it exists
                    if (showMoreBtn) {
                        activitiesContainer.insertBefore(activityItem, showMoreBtn);
                    } else {
                        activitiesContainer.appendChild(activityItem);
                    }
                });
            }

        } else {
            console.error('Error fetching user profile:', data.message);
            alert(`Failed to load profile: ${data.message}`);
            // Optionally redirect or show a user-friendly error message on the page
        }

    } catch (error) {
        console.error('Network error or failed to parse JSON:', error);
        alert('An error occurred while loading your profile.');
        // Optionally redirect or show a user-friendly error message on the page
    }
});
