document.addEventListener('DOMContentLoaded', function() {
    // Load user profile data
    loadProfileData();
    
    // Initialize event listeners
    initializeEventListeners();
});

function loadProfileData() {
    // Check if user is logged in first
    fetch('../php/check_login.php')
        .then(response => response.json())
        .then(data => {
            console.log('Login check result:', data); // Debug log
            if (data.loggedIn) {
                // User is logged in, load their profile data
                loadUserProfile(data.userId || data.Id);
            } else {
                // User not logged in, redirect to login
                console.log('User not logged in, redirecting...');
                window.location.href = 'LoginPage.html';
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
            // Don't redirect immediately, try to load profile anyway for testing
            loadUserProfile();
        });
}

function loadUserProfile(userId) {
    // Fetch user profile data from the server
    fetch('../php/get_profile.php')
        .then(response => {
            console.log('Profile fetch response status:', response.status); // Debug log
            return response.json();
        })
        .then(data => {
            console.log('Profile data received:', data); // Debug log
            if (data.success) {
                updateProfileDisplay(data.user);
            } else {
                console.error('Error loading profile:', data.message);
                // Use default data if server data fails
                useDefaultProfileData();
            }
        })
        .catch(error => {
            console.error('Error fetching profile:', error);
            // Use default data if fetch fails
            useDefaultProfileData();
        });
}

function updateProfileDisplay(userData) {
    // Update profile header
    const userName = document.getElementById('userName');
    const userEmail = document.getElementById('userEmail');
    const profileAvatar = document.querySelector('.profile-avatar');
    
    if (userName) userName.textContent = userData.Username || 'User Name';
    if (userEmail) userEmail.textContent = userData.Email || 'user@email.com';
    
    // Update avatar initials
    if (profileAvatar && userData.Username) {
        const initials = getInitials(userData.Username);
        profileAvatar.textContent = initials;
    }
    
    // Update general information
    const createDate = document.getElementById('createDate');
    const userStatus = document.getElementById('userStatus');
    const userPhone = document.getElementById('userPhone');
    const userAddress = document.getElementById('userAddress');
    
    if (createDate) createDate.textContent = formatDate(userData.created_at) || 'N/A';
    if (userStatus) userStatus.textContent = userData.status || 'Active';
    if (userPhone) userPhone.textContent = userData.Phone_number || 'Not provided';
    if (userAddress) userAddress.textContent = userData.Address || 'Not provided';
    
    // Update points balance
    const pointsBalance = document.getElementById('pointsBalance');
    const availablePoints = document.getElementById('availablePoints');
    const points = userData.Points || 0;
    
    if (pointsBalance) pointsBalance.textContent = points.toLocaleString();
    if (availablePoints) availablePoints.textContent = points.toLocaleString();
    
    // Update membership tier based on points
    const memberTier = document.getElementById('memberTier');
    if (memberTier) {
        memberTier.textContent = getMembershipTier(points);
    }

    const memberBenefit = document.getElementById('memberBenefit');
    if (memberBenefit)  {
        memberBenefit.textContent = getMembershipBenefits(points);
    }
}

function useDefaultProfileData() {
    // Use the data that's already in the HTML as fallback
    console.log('Using default profile data');
}

function getInitials(name) {
    if (!name) return 'U';
    const names = name.split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[1][0]).toUpperCase();
    }
    return names[0][0].toUpperCase();
}

function formatDate(dateString) {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function getMembershipTier(points) {
    if (points >= 15000) return 'Platinum';
    if (points >= 10000) return 'Gold';
    if (points >= 5000) return 'Silver';
    return 'Bronze';
}

function getMembershipBenefits(points){
    const tier = getMembershipTier(points);
    if (tier === 'Platinum') return 'Platinum Rewards';
    if (tier === 'Gold') return 'Gold Rewards';
    if (tier === 'Silver') return 'Silver Rewards';
    return 'Bronze Rewards';
}

function initializeEventListeners() {
    console.log('Initializing event listeners...'); // Debug log
    
    // Edit button functionality
    const editBtn = document.getElementById('editToggleBtn');
    const editContainer = document.getElementById('editFormContainer');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const editForm = document.getElementById('editProfileForm');
    
    console.log('Elements found:', {
        editBtn: !!editBtn,
        editContainer: !!editContainer,
        cancelBtn: !!cancelBtn,
        editForm: !!editForm
    }); // Debug log
    
    if (editBtn) {
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Edit button clicked!'); // Debug log
            showEditForm();
        });
    } else {
        console.error('Edit button not found!');
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            console.log('Cancel button clicked');
            hideEditForm();
        });
    }
    
    if (editForm) {
        editForm.addEventListener('submit', handleProfileUpdate);
    }

    // Initialize transaction tabs
    initializeTransactionTabs();

    // Initialize loyalty progress
    initializeLoyaltyProgress();
    
    // Load transactions and activities
    loadTransactionHistory();
    loadActivities();
}

function initializeTransactionTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show corresponding content
            const targetContent = document.getElementById(`${tabId}-tab`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
            
            console.log(`Switched to ${tabId} tab`);
        });
    });
}

function initializeLoyaltyProgress() {
    // This will be called after profile data is loaded to update progress
    updateLoyaltyProgress(0); // Default to 0 points initially
}

function updateLoyaltyProgress(points) {
    const currentTierBadge = document.getElementById('currentTierBadge');
    const progressFill = document.getElementById('progressFill');
    const progressPercentage = document.getElementById('progressPercentage');
    const progressText = document.getElementById('progressText');

    const tier = getMembershipTier(points);
    const tierInfo = getTierInfo(points);

    if (currentTierBadge) {
        currentTierBadge.textContent = tier;
        currentTierBadge.className = `tier-badge ${tier.toLowerCase()}`;
    }

    if (progressFill) {
        progressFill.style.width = `${tierInfo.percentage}%`;
    }

    if (progressPercentage) {
        progressPercentage.textContent = `${tierInfo.percentage}%`;
    }

    if (progressText) {
        progressText.textContent = tierInfo.text;
    }

    // Initialize points chart
    initializePointsChart();
}

function getTierInfo(points) {
    if (points >= 5000) {
        return {
            percentage: 100,
            text: 'You have reached the highest tier - Platinum!'
        };
    } else if (points >= 4000) {
        const remaining = 5000 - points;
        const percentage = ((points - 4000) / 1000) * 100;
        return {
            percentage: Math.round(percentage),
            text: `You need ${remaining} more points to reach Platinum Tier.`
        };
    } else if (points >= 3000) {
        const remaining = 4000 - points;
        const percentage = ((points - 3000) / 1000) * 100;
        return {
            percentage: Math.round(percentage),
            text: `You need ${remaining} more points to reach Gold Tier.`
        };
    } else {
        const remaining = 3000 - points;
        const percentage = (points / 3000) * 100;
        return {
            percentage: Math.round(percentage),
            text: `You need ${remaining} more points to reach Silver Tier.`
        };
    }
}

function initializePointsChart() {
    const canvas = document.getElementById('pointsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    
    // Sample data - you can replace this with real data from your backend
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    const pointsData = [1800, 1600, 2700, 2400, 3600, 3200];

    // Set canvas size
    canvas.width = 400;
    canvas.height = 200;

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Chart settings
    const padding = 40;
    const chartWidth = canvas.width - (padding * 2);
    const chartHeight = canvas.height - (padding * 2);
    const maxPoints = Math.max(...pointsData);
    const minPoints = Math.min(...pointsData);
    const pointRange = maxPoints - minPoints || 1;

    // Draw axes
    ctx.strokeStyle = '#e9ecef';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, canvas.height - padding);
    ctx.lineTo(canvas.width - padding, canvas.height - padding);
    ctx.stroke();

    // Draw grid lines
    ctx.strokeStyle = '#f8f9fa';
    for (let i = 1; i < 5; i++) {
        const y = padding + (chartHeight / 5) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }

    // Draw line chart
    ctx.strokeStyle = '#007bff';
    ctx.lineWidth = 3;
    ctx.fillStyle = 'rgba(0, 123, 255, 0.1)';
    
    ctx.beginPath();
    pointsData.forEach((points, index) => {
        const x = padding + (chartWidth / (pointsData.length - 1)) * index;
        const y = canvas.height - padding - ((points - minPoints) / pointRange) * chartHeight;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    
    // Fill area under curve
    ctx.lineTo(canvas.width - padding, canvas.height - padding);
    ctx.lineTo(padding, canvas.height - padding);
    ctx.closePath();
    ctx.fill();
    
    // Draw the line
    ctx.beginPath();
    pointsData.forEach((points, index) => {
        const x = padding + (chartWidth / (pointsData.length - 1)) * index;
        const y = canvas.height - padding - ((points - minPoints) / pointRange) * chartHeight;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    ctx.stroke();

    // Draw points
    ctx.fillStyle = '#007bff';
    pointsData.forEach((points, index) => {
        const x = padding + (chartWidth / (pointsData.length - 1)) * index;
        const y = canvas.height - padding - ((points - minPoints) / pointRange) * chartHeight;
        
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fill();
    });

    // Draw labels
    ctx.fillStyle = '#6c757d';
    ctx.font = '12px Arial';
    ctx.textAlign = 'center';
    
    months.forEach((month, index) => {
        const x = padding + (chartWidth / (pointsData.length - 1)) * index;
        ctx.fillText(month, x, canvas.height - padding + 20);
    });
}

function showEditForm() {
    console.log('Showing edit form...'); // Debug log
    const editContainer = document.getElementById('editFormContainer');
    
    if (editContainer) {
        // Fill form with current data
        populateEditForm();
        
        // Show the form
        editContainer.style.display = 'block';
        
        // Scroll to form
        editContainer.scrollIntoView({ behavior: 'smooth' });
        
        console.log('Edit form should now be visible'); // Debug log
    } else {
        console.error('Edit form container not found!');
    }
}

function hideEditForm() {
    console.log('Hiding edit form...'); // Debug log
    const editContainer = document.getElementById('editFormContainer');
    
    if (editContainer) {
        editContainer.style.display = 'none';
        clearEditForm();
    }
}

function populateEditForm() {
    // Get current profile data from the page
    const userName = document.getElementById('userName')?.textContent || '';
    const userEmail = document.getElementById('userEmail')?.textContent || '';
    const userPhone = document.getElementById('userPhone')?.textContent || '';
    const userAddress = document.getElementById('userAddress')?.textContent || '';
    
    // Fill the form fields
    const editUsername = document.getElementById('editUsername');
    const editEmail = document.getElementById('editEmail');
    const editPhone = document.getElementById('editPhone');
    const editAddress = document.getElementById('editAddress');
    
    if (editUsername) editUsername.value = userName;
    if (editEmail) editEmail.value = userEmail;
    if (editPhone) editPhone.value = userPhone !== 'Not provided' ? userPhone : '';
    if (editAddress) editAddress.value = userAddress !== 'Not provided' ? userAddress : '';
    
    console.log('Form filled with current data');
}

function clearEditForm() {
    const editForm = document.getElementById('editProfileForm');
    if (editForm) {
        editForm.reset();
    }
    
    const messageDiv = document.getElementById('editMessage');
    if (messageDiv) {
        messageDiv.style.display = 'none';
        messageDiv.className = 'message';
    }
}

function handleProfileUpdate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const messageDiv = document.getElementById('editMessage');
    const submitBtn = e.target.querySelector('.btn-save');
    
    // Simple validation - just check required fields
    const username = formData.get('Username');
    const email = formData.get('Email');
    
    if (!username || !email) {
        showMessage(messageDiv, 'Name and email are required.', 'error');
        return;
    }
    
    // Show loading state
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }
    
    // Send update request
    fetch('../php/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, 'Profile updated successfully!', 'success');
            // Update the profile display
            updateProfileDisplay(data.user);
            // Hide form after short delay
            setTimeout(() => {
                hideEditForm();
            }, 2000);
        } else {
            showMessage(messageDiv, data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage(messageDiv, 'Update failed. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Changes';
        }
    });
}

function showMessage(messageDiv, message, type) {
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
    }
}

function loadTransactionHistory() {
    // Fetch transaction history from server
    fetch('../php/get_transactions.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions) {
                updateTransactionDisplay(data.transactions);
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            // Keep default transactions if fetch fails
        });
}

function updateTransactionDisplay(transactions) {
    const transactionList = document.getElementById('transactionList');
    if (!transactionList || transactions.length === 0) return;
    
    transactionList.innerHTML = '';
    
    transactions.forEach(transaction => {
        const transactionItem = createTransactionElement(transaction);
        transactionList.appendChild(transactionItem);
    });
}

function createTransactionElement(transaction) {
    const item = document.createElement('div');
    item.className = 'transaction-item';
    
    const isCredit = transaction.type === 'credit' || transaction.amount > 0;
    const iconClass = isCredit ? 'credit' : 'debit';
    const iconSymbol = isCredit ? 'fas fa-plus' : 'fas fa-minus';
    const amountClass = isCredit ? 'credit' : 'debit';
    const amountPrefix = isCredit ? '+' : '-';
    
    item.innerHTML = `
        <div class="transaction-info">
            <div class="transaction-icon ${iconClass}">
                <i class="${iconSymbol}"></i>
            </div>
            <div class="transaction-details">
                <h4>${transaction.description || 'Transaction'}</h4>
                <p>${formatTransactionDate(transaction.date)}</p>
            </div>
        </div>
        <div class="transaction-amount ${amountClass}">${amountPrefix}$${Math.abs(transaction.amount)}</div>
    `;
    
    return item;
}

function formatTransactionDate(dateString) {
    if (!dateString) return 'Unknown date';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function loadActivities() {
    // Fetch activities from server
    fetch('../php/get_activities.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.activities) {
                updateActivitiesDisplay(data.activities);
            }
        })
        .catch(error => {
            console.error('Error loading activities:', error);
            // Keep default activities if fetch fails
        });
}

function updateActivitiesDisplay(activities) {
    const activitiesList = document.getElementById('activitiesList');
    if (!activitiesList || activities.length === 0) return;
    
    activitiesList.innerHTML = '';
    
    activities.forEach(activity => {
        const activityItem = createActivityElement(activity);
        activitiesList.appendChild(activityItem);
    });
}

function createActivityElement(activity) {
    const item = document.createElement('div');
    item.className = 'activity-item';
    
    const icon = getActivityIcon(activity.type);
    
    item.innerHTML = `
        <div class="activity-icon">
            <i class="${icon}"></i>
        </div>
        <div class="activity-text">
            ${activity.description}
        </div>
        <div class="activity-time">
            ${formatActivityDate(activity.date)}
        </div>
    `;
    
    return item;
}

function getActivityIcon(activityType) {
    const icons = {
        'voucher': 'fas fa-ticket-alt',
        'purchase': 'fas fa-shopping-cart',
        'redemption': 'fas fa-gift',
        'login': 'fas fa-sign-in-alt',
        'registration': 'fas fa-user-plus',
        'default': 'fas fa-circle'
    };
    
    return icons[activityType] || icons.default;
}

function formatActivityDate(dateString) {
    if (!dateString) return 'Unknown date';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        day: '2-digit',
        month: '2-digit',
        year: '2-digit'
    }) + ' • ' + date.toLocaleDateString('en-US', { 
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}