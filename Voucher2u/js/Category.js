// Category.js

document.addEventListener('DOMContentLoaded', function() {
    const categoryTitleElement = document.getElementById('category-title');
    const categoryDescriptionElement = document.getElementById('category-description');
    const categoryHeroSection = document.querySelector('.category-hero');
    const voucherListElement = document.getElementById('voucher-list');

    const params = new URLSearchParams(window.location.search);
    const categoryName = params.get('name') || 'general';

    // Burger menu script (retained from original Category.html)
    const burgerMenu = document.querySelector('.burger-menu');
    const navLinks = document.querySelector('.nav-links');

    if (burgerMenu && navLinks) {
        burgerMenu.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }

    // Function to fetch and display vouchers
    async function fetchAndDisplayVouchers(category) {
        try {
            const response = await fetch(`../php/fetch_vouchers.php?name=${category}`);
            const data = await response.json();

            if (data.success) {
                // Update category hero section (you might want to customize banners based on category)
                let title = '';
                let description = '';
                let bannerImage = '';

                switch(category) {
                    case 'travel':
                        title = 'Travel Vouchers';
                        description = 'Exclusive deals on flights, hotels, and holiday packages.';
                        bannerImage = '../assets/travel_banner.png';
                        break;
                    case 'food':
                        title = 'Food & Dining Vouchers';
                        description = 'Delicious discounts at restaurants, cafes, and delivery.';
                        bannerImage = '../assets/food_banner.png';
                        break;
                    case 'shopping':
                        title = 'Shopping & Retail Vouchers';
                        description = 'Save on fashion, electronics, and home goods.';
                        bannerImage = '../assets/shopping_banner.png';
                        break;
                    default:
                        title = 'Voucher Category';
                        description = 'Browse available vouchers.';
                        bannerImage = '../assets/default_banner.png'; // Default banner
                }
                
                if (categoryTitleElement) categoryTitleElement.textContent = title;
                if (categoryDescriptionElement) categoryDescriptionElement.textContent = description;
                if (categoryHeroSection) categoryHeroSection.style.backgroundImage = `url('${bannerImage}')`;

                voucherListElement.innerHTML = ''; // Clear previous vouchers

                if (data.vouchers.length > 0) {
                    data.vouchers.forEach(voucher => {
                        const offerItem = document.createElement('div');
                        offerItem.classList.add('offer-item');
                        offerItem.style.cursor = 'pointer'; // Indicate it's clickable
                        offerItem.dataset.voucherId = voucher.id; // Store ID for navigation
                        offerItem.innerHTML = `
                            <img src="${voucher.image}" alt="${voucher.title}">
                            <h3>${voucher.title}</h3>
                            <p>${voucher.description}</p>
                            <div class="points">${voucher.points} Points</div>
                        `;
                        offerItem.addEventListener('click', () => {
                            window.location.href = `productDetails.html?id=${voucher.id}`;
                        });
                        voucherListElement.appendChild(offerItem);
                    });
                } else {
                    voucherListElement.innerHTML = '<p>No vouchers available in this category.</p>';
                }

            } else {
                console.error('Error fetching vouchers:', data.message);
                voucherListElement.innerHTML = `<p>Error loading vouchers: ${data.message}</p>`;
            }
        } catch (error) {
            console.error('Network error or failed to parse JSON:', error);
            voucherListElement.innerHTML = '<p>Failed to load vouchers. Please try again later.</p>';
        }
    }

    fetchAndDisplayVouchers(categoryName);
});
