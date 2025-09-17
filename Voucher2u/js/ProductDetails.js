// ProductDetails.js

document.addEventListener('DOMContentLoaded', async function() {
    const productImage = document.getElementById('product-image');
    const productTitle = document.getElementById('product-title');
    const productDescription = document.getElementById('product-description');
    const termsConditionsList = document.getElementById('terms-conditions-list');
    const redeemNowBtn = document.querySelector('.redeem-now-btn');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const userPoints = document.getElementById('user-points');
    const cartCountSpan = document.getElementById('cart-count');

    const params = new URLSearchParams(window.location.search);
    const productId = params.get('id');

    let currentProduct = null; // To store fetched product details

    // Function to add product to cart (now interacts with backend)
    async function addToCart(product) {
        const userId = localStorage.getItem('userId');
        if (!userId) {
            alert('Please log in to add items to your cart.');
            window.location.href = 'LoginPage.html'; // Redirect to login page
            return;
        }

        try {
            const response = await fetch('../php/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    voucher_id: product.id,
                    user_id: userId,
                    quantity: 1 // Always add 1 from product details page
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                window.dispatchEvent(new Event('cartUpdated')); // Notify global cart count
                window.location.href = 'ShoppingCart.html'; // Navigate to shopping cart page
            } else {
                alert(`Failed to add to cart: ${data.message}`);
                console.error('Add to Cart Error:', data.message);
            }
        } catch (error) {
            alert('An error occurred while adding to cart.');
            console.error('Network error or failed to parse JSON:', error);
        }
    }

    // Fetch product details
    if (productId) {
        try {
            const response = await fetch(`../php/fetch_product_details.php?id=${productId}`);
            const data = await response.json();

            if (data.success && data.product) {
                currentProduct = data.product;
                productImage.src = currentProduct.image;
                productImage.alt = currentProduct.title;
                productTitle.textContent = currentProduct.title;
                productDescription.textContent = currentProduct.description;
                userPoints.textContent = `${currentProduct.points} Points`; // Update points display

                // Populate terms & conditions
                if (termsConditionsList) {
                    termsConditionsList.innerHTML = '';
                    // Assuming terms_and_condition is a string with newlines for list items
                    currentProduct.terms_and_condition.split('.').filter(item => item.trim() !== '').forEach(term => {
                        const li = document.createElement('li');
                        li.textContent = term.trim() + '.';
                        termsConditionsList.appendChild(li);
                    });
                }

                // Add to Cart button functionality
                if (addToCartBtn) {
                    addToCartBtn.addEventListener('click', () => {
                        addToCart(currentProduct);
                    });
                }

                // Redeem Now button functionality (placeholder for now)
                if (redeemNowBtn) {
                    redeemNowBtn.addEventListener('click', () => {
                        alert(`Redeem functionality for ${currentProduct.title} will be implemented here.`);
                    });
                }

            } else {
                console.error('Error fetching product details:', data.message);
                productTitle.textContent = 'Product Not Found';
                productDescription.textContent = data.message;
            }
        } catch (error) {
            console.error('Network error or failed to parse JSON:', error);
            productTitle.textContent = 'Error';
            productDescription.textContent = 'Failed to load product details. Please try again later.';
        }
    } else {
        productTitle.textContent = 'Invalid Product ID';
        productDescription.textContent = 'No product ID provided in the URL.';
    }
});