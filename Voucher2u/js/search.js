document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const searchIcon = document.querySelector('.search-bar .fa-search');

    // Determine which container to render results into based on the current page
    const resultsContainer = document.getElementById('featured-vouchers-container') || document.getElementById('voucher-list');
    const originalContentContainer = resultsContainer.innerHTML; // Store original content

    if (!searchInput || !searchIcon || !resultsContainer) {
        console.error('Search elements or results container not found.');
        return;
    }

    const performSearch = () => {
        const query = searchInput.value.trim();

        if (query.length === 0) {
            // If search query is empty, revert to original content (e.g., featured vouchers or category vouchers)
            // For homepage, this means re-triggering featuredVouchers.js
            if (document.getElementById('featured-vouchers-container')) {
                // Logic to re-load featured vouchers (you might need to call a function from featuredVouchers.js)
                window.location.reload(); // Simple reload for now to bring back featured offers
            } else if (document.getElementById('voucher-list')) {
                // Logic to re-load category vouchers (you might need to call a function from Category.js)
                const urlParams = new URLSearchParams(window.location.search);
                const categoryName = urlParams.get('name');
                if (categoryName) {
                    window.location.reload(); // Simple reload for now to bring back category vouchers
                }
            }
            return;
        }

        fetch(`../php/search_vouchers.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.vouchers.length > 0) {
                    renderSearchResults(data.vouchers);
                } else {
                    resultsContainer.innerHTML = `<p class="no-results">${data.message || 'No results found.'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error during search:', error);
                resultsContainer.innerHTML = '<p class="error-message">Failed to perform search. Please try again later.</p>';
            });
    };

    searchIcon.addEventListener('click', performSearch);

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

    function renderSearchResults(vouchers) {
        resultsContainer.innerHTML = ''; // Clear previous content
        vouchers.forEach(voucher => {
            const offerItem = document.createElement('div');
            offerItem.classList.add('offer-item'); // Reusing existing styling class
            offerItem.innerHTML = `
                <a href="productDetails.html?id=${voucher.Id}">
                    <img src="${voucher.Image_Path}" alt="${voucher.Title}">
                    <div class="points">${voucher.Points} Points</div>
                    <h3>${voucher.Title}</h3>
                    <p>${voucher.Description}</p>
                    <span class="btn">View Details</span>
                </a>
            `;
            resultsContainer.appendChild(offerItem);
        });
    }
});
