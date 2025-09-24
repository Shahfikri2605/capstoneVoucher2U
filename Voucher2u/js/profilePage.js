console.log('profilePage.js loaded v20250924');

document.addEventListener('DOMContentLoaded', function() {
    // Load user profile data
    loadProfileData();
    
    // Initialize event listeners
    initializeEventListeners();
});

// Simple HTML escape helper to prevent insertion of raw HTML from server data
function escapeHtml(unsafe) {
    if (!unsafe && unsafe !== 0) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
    
    // Update avatar image or initials without overwriting innerHTML
    const avatarImg = document.getElementById('avatarImg');
    const avatarInitials = document.getElementById('avatarInitials');
    // If server provided a profile image path, show it; otherwise show initials
    if (userData.Profile_image) {
        if (avatarImg) {
            avatarImg.src = userData.Profile_image;
            avatarImg.style.display = 'block';
        }
        if (avatarInitials) avatarInitials.style.display = 'none';
    } else {
        if (avatarImg) {
            avatarImg.src = '';
            avatarImg.style.display = 'none';
        }
        if (avatarInitials) {
            avatarInitials.style.display = 'flex';
            avatarInitials.textContent = getInitials(userData.Username || '');
        }
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
    
    // Update loyalty progress
    updateLoyaltyProgress(points);
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
    
    // Modal Edit Form functionality
    const editBtn = document.getElementById('editToggleBtn');
    const editModalOverlay = document.getElementById('editModalOverlay');
    const editContainer = document.getElementById('editFormContainer');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const editForm = document.getElementById('editProfileForm');
    
    // Password fields
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    console.log('Modal elements found:', {
        editBtn: !!editBtn,
        editModalOverlay: !!editModalOverlay,
        editContainer: !!editContainer,
        cancelBtn: !!cancelBtn,
        modalCloseBtn: !!modalCloseBtn,
        editForm: !!editForm
    }); // Debug log
    
    if (editBtn) {
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Edit button clicked!'); // Debug log
            showEditModal();
        });
    } else {
        console.error('Edit button not found!');
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Cancel button clicked');
            hideEditModal();
        });
    }
    
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Modal close button clicked');
            hideEditModal();
        });
    }
    
    // Close modal when clicking outside of it
    if (editModalOverlay) {
        editModalOverlay.addEventListener('click', function(e) {
            if (e.target === editModalOverlay) {
                console.log('Clicked outside modal');
                hideEditModal();
            }
        });
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && editModalOverlay && editModalOverlay.classList.contains('active')) {
            console.log('Escape key pressed');
            hideEditModal();
        }
    });
    
    if (editForm) {
        editForm.addEventListener('submit', handleProfileUpdate);
    }
    
    // Simple password validation for matching passwords
    if (newPasswordInput && confirmPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            validatePasswordMatch();
        });
        
        confirmPasswordInput.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }

    // Initialize transaction tabs
    initializeTransactionTabs();

    // Initialize loyalty progress
    initializeLoyaltyProgress();
    
    // Load transactions and activities
    loadTransactionHistory();
    loadActivities();

    // Avatar upload handlers
    const profileAvatar = document.getElementById('profileAvatar');
    const avatarFileInput = document.getElementById('avatarFileInput');
    const avatarImg = document.getElementById('avatarImg');
    const avatarInitials = document.getElementById('avatarInitials');
    const avatarEditBtn = document.getElementById('avatarEditBtn');

    function openAvatarPicker() {
        if (avatarFileInput) avatarFileInput.click();
    }

    if (profileAvatar) {
        profileAvatar.addEventListener('click', function(e) {
            // If user clicks the container (not the edit button), open picker
            openAvatarPicker();
        });
    }

    if (avatarEditBtn) {
        avatarEditBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            openAvatarPicker();
        });
    }

    if (avatarFileInput) {
        avatarFileInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            // Validate file type and size (limit to 5MB)
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size should be 5MB or less.');
                return;
            }

            // Preview image locally
            const reader = new FileReader();
            reader.onload = function(evt) {
                if (avatarImg) {
                    avatarImg.src = evt.target.result;
                    avatarImg.style.display = 'block';
                }
                if (avatarInitials) {
                    avatarInitials.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);

            // Upload to server
            const uploadData = new FormData();
            uploadData.append('profile_image', file);

            fetch('../php/upload_profile_image.php', {
                method: 'POST',
                body: uploadData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // If server returns the image path, update src
                    if (data.imageUrl && avatarImg) {
                        avatarImg.src = data.imageUrl + '?t=' + Date.now();
                        avatarImg.style.display = 'block';
                        if (avatarInitials) avatarInitials.style.display = 'none';
                    }
                    // Optionally update session/local UI
                    console.log('Profile image updated successfully');
                } else {
                    alert(data.message || 'Failed to upload image');
                    // revert preview
                    if (avatarImg) {
                        avatarImg.src = '';
                        avatarImg.style.display = 'none';
                    }
                    if (avatarInitials) avatarInitials.style.display = 'flex';
                }
            })
            .catch(err => {
                console.error('Upload error', err);
                alert('An error occurred while uploading the image.');
                if (avatarImg) {
                    avatarImg.src = '';
                    avatarImg.style.display = 'none';
                }
                if (avatarInitials) avatarInitials.style.display = 'flex';
            });
        });
    }
}

function showEditModal() {
    console.log('Showing edit modal...'); // Debug log
    const editModalOverlay = document.getElementById('editModalOverlay');
    
    if (editModalOverlay) {
        // Fill form with current data
        populateEditForm();
        
        // Show the modal
        editModalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent body scroll
        
        // Focus first input after animation
        setTimeout(() => {
            const firstInput = document.getElementById('editUsername');
            if (firstInput) {
                firstInput.focus();
            }
        }, 300);
        
        console.log('Edit modal should now be visible'); // Debug log
    } else {
        console.error('Edit modal overlay not found!');
    }
}

function hideEditModal() {
    console.log('Hiding edit modal...'); // Debug log
    const editModalOverlay = document.getElementById('editModalOverlay');
    
    if (editModalOverlay) {
        editModalOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restore body scroll
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
    
    console.log('Form filled with current data:', {
        username: userName,
        email: userEmail,
        phone: userPhone,
        address: userAddress
    });
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
    
    console.log('Edit form cleared');
}

function validatePasswordMatch() {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    if (!newPasswordInput || !confirmPasswordInput) return;
    
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    // Only validate if both fields have content
    if (newPassword && confirmPassword) {
        if (newPassword !== confirmPassword) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
            console.log('Password validation: passwords do not match');
        } else {
            confirmPasswordInput.setCustomValidity('');
            console.log('Password validation: passwords match');
        }
    } else {
        confirmPasswordInput.setCustomValidity('');
    }
}

function handleProfileUpdate(e) {
    e.preventDefault();
    
    console.log('Profile update form submitted'); // Debug log
    
    const formData = new FormData(e.target);
    const messageDiv = document.getElementById('editMessage');
    const submitBtn = e.target.querySelector('.btn-save') || e.target.querySelector('button[type="submit"]');
    
    // Get form values for validation
    const username = formData.get('Username');
    const email = formData.get('Email');
    const newPassword = formData.get('new_password');
    const currentPassword = formData.get('current_password');
    const confirmPassword = formData.get('confirm_password');
    
    console.log('Form data being processed:', {
        username: username,
        email: email,
        phone: formData.get('Phone_number'),
        address: formData.get('Address'),
        hasCurrentPassword: !!currentPassword,
        hasNewPassword: !!newPassword,
        hasConfirmPassword: !!confirmPassword
    });
    
    // Basic validation
    if (!username || !email) {
        showMessage(messageDiv, 'Username and email are required.', 'error');
        return;
    }
    
    // Email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showMessage(messageDiv, 'Please enter a valid email address.', 'error');
        return;
    }
    
    // Password validation if password change is requested
    if (newPassword) {
        if (!currentPassword) {
            showMessage(messageDiv, 'Current password is required to set a new password.', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showMessage(messageDiv, 'New passwords do not match.', 'error');
            return;
        }
        
        if (newPassword.length < 8) {
            showMessage(messageDiv, 'New password must be at least 8 characters long.', 'error');
            return;
        }
    }
    
    // Show loading state
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    console.log('Sending update request to server...');
    
    // Send update request
    fetch('../php/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showMessage(messageDiv, data.message || 'Profile updated successfully!', 'success');
            // Update the profile display with new data
            if (data.user) {
                updateProfileDisplay(data.user);
            }
            // Hide form after short delay
            setTimeout(() => {
                hideEditModal();
            }, 2000);
        } else {
            showMessage(messageDiv, data.message || 'Update failed. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showMessage(messageDiv, 'Update failed. Please check your connection and try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    });
}

function showMessage(messageDiv, message, type) {
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        console.log(`Message displayed: ${type} - ${message}`);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }
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
    if (points >= 15000) {
        return {
            percentage: 100,
            text: 'You have reached the highest tier - Platinum!'
        };
    } else if (points >= 10000) {
        const remaining = 15000 - points;
        const percentage = ((points - 10000) / 5000) * 100;
        return {
            percentage: Math.round(percentage),
            text: `You need ${remaining} more points to reach Platinum Tier.`
        };
    } else if (points >= 5000) {
        const remaining = 10000 - points;
        const percentage = ((points - 5000) / 5000) * 100;
        return {
            percentage: Math.round(percentage),
            text: `You need ${remaining} more points to reach Gold Tier.`
        };
    } else {
        const remaining = 5000 - points;
        const percentage = (points / 5000) * 100;
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

    // Create header row that starts from the voucher title column
    const header = document.createElement('div');
    header.className = 'transaction-item transaction-header';
    header.innerHTML = `
        <div class="transaction-row">
            <div class="transaction-start"></div>
            <div class="transaction-type"></div>
            <div class="transaction-title header">Voucher</div>
            <div class="transaction-qty header">Quantity</div>
            <div class="transaction-date header">Date</div>
            <div class="transaction-amount header">Points</div>
        </div>
    `;
    transactionList.appendChild(header);

    transactions.forEach(transaction => {
        const transactionItem = createTransactionElement(transaction);
        transactionList.appendChild(transactionItem);
    });
}

function createTransactionElement(transaction) {
    const item = document.createElement('div');
    item.className = 'transaction-item';
    // transaction expected fields from server: title, quantity, points_cost, completed_date
    const title = transaction.title || 'Transaction';
    const quantity = transaction.quantity || 1;
    const pointsCost = transaction.points_cost || 0;
    const completedDate = transaction.completed_date || transaction.date || null;
    // Layout: [tick icon] | "Voucher Redemption" | Title | Quantity | Date | points
    item.innerHTML = `
        <div class="transaction-row">
            <div class="transaction-start">
                <div class="transaction-icon completed"><i class="fas fa-check"></i></div>
            </div>
            <div class="transaction-type">Voucher Redemption</div>
            <div class="transaction-title">${escapeHtml(title)}</div>
            <div class="transaction-qty">${quantity}x</div>
            <div class="transaction-date">${formatTransactionDate(completedDate)}</div>
            <div class="transaction-amount completed">${pointsCost}</div>
        </div>
    `;
    
    return item;
}

function formatTransactionDate(dateString) {
    if (!dateString) return 'Unknown date';
    const date = new Date(dateString);
    // Return date only (no time) because DB stores date without time
    return date.toLocaleDateString('en-US', {
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
        'profile_update': 'fas fa-user-edit',
        'password_change': 'fas fa-key',
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