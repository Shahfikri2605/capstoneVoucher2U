document.addEventListener('DOMContentLoaded', () => {
    const featuredVouchersContainer = document.getElementById('featured-vouchers-container');

    if (!featuredVouchersContainer) {
        console.error('Featured vouchers container not found.');
        return;
    }

    fetch('../php/fetch_featured_vouchers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.vouchers.length > 0) {
                renderFeaturedVouchers(data.vouchers);
            } else {
                featuredVouchersContainer.innerHTML = '<p>' + (data.message || 'No featured vouchers available.') + '</p>';
                console.warn(data.message || 'No featured vouchers found.');
            }
        })
        .catch(error => {
            console.error('Error fetching featured vouchers:', error);
            featuredVouchersContainer.innerHTML = '<p>Failed to load featured vouchers. Please try again later.</p>';
        });

    function renderFeaturedVouchers(vouchers) {
        featuredVouchersContainer.innerHTML = ''; // Clear existing content
        vouchers.forEach(voucher => {
            const offerItem = document.createElement('div');
            offerItem.classList.add('offer-item');
            offerItem.innerHTML = `
                <a href="productDetails.html?id=${voucher.Id}">
                    <img src="${voucher.Image_Path}" alt="${voucher.Title}">
                    <div class="points">${voucher.Points} Points</div>
                    <h3>${voucher.Title}</h3>
                    <p>${voucher.Description}</p>
                    <span class="btn">Redeem Now</span>
                </a>
            `;
            featuredVouchersContainer.appendChild(offerItem);
        });
    }
});
