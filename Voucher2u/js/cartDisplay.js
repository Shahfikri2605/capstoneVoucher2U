// cartDisplay.js

document.addEventListener('DOMContentLoaded', function() {
    async function updateGlobalCartCount() {
        const userId = localStorage.getItem('userId');
        let totalItems = 0;

        if (userId) {
            try {
                const response = await fetch(`../php/fetch_cart_items.php?user_id=${userId}`);
                const data = await response.json();

                if (data.success && data.cart_items) {
                    totalItems = data.cart_items.reduce((sum, item) => sum + item.quantity, 0);
                } else {
                    console.error('Error fetching global cart count:', data.message);
                }
            } catch (error) {
                console.error('Network error or failed to parse JSON for global cart count:', error);
            }
        } else {
            // If user is not logged in, cart is empty
            totalItems = 0;
        }
        
        // Update all elements with id="cart-count"
        document.querySelectorAll('#cart-count').forEach(element => {
            element.textContent = `Cart (${totalItems})`;
        });
    }

    // Initial update
    updateGlobalCartCount();

    // Listen for custom event to update cart count (e.g., after adding to cart)
    window.addEventListener('cartUpdated', updateGlobalCartCount);
});
