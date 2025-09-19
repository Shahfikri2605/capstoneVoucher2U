// ProductDetails.js

document.addEventListener('DOMContentLoaded', async function() {
    const productImage = document.getElementById('product-image');
    const productTitle = document.getElementById('product-title');
    const productDescription = document.getElementById('product-description');
    const termsConditionsList = document.getElementById('terms-conditions-list');
    const redeemNowBtn = document.getElementById('redeem-now-btn');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const quantityInput = document.getElementById('quantity-input');
    const minusBtn = document.querySelector('.minus-btn');
    const plusBtn = document.querySelector('.plus-btn');
    const userPoints = document.getElementById('user-points');
    const cartCountSpan = document.getElementById('cart-count');

    const params = new URLSearchParams(window.location.search);
    const productId = params.get('id');

    let currentProduct = null; // To store fetched product details

    // Function to check user points and set button states
    function checkPointsAndSetButtonStates() {
        const userAvailablePoints = parseInt(localStorage.getItem('userPoints') || '0');
        const voucherPointsNeeded = currentProduct ? currentProduct.points : 0;

        if (redeemNowBtn) {
            if (userAvailablePoints >= voucherPointsNeeded) {
                redeemNowBtn.disabled = false;
                redeemNowBtn.textContent = 'REDEEM NOW';
                redeemNowBtn.style.backgroundColor = ''; // Reset to default
            } else {
                redeemNowBtn.disabled = true;
                redeemNowBtn.textContent = "Insufficient Points";
                redeemNowBtn.title = `You need ${voucherPointsNeeded} points`;
            }
        }

        if (addToCartBtn) {
            if (userAvailablePoints >= voucherPointsNeeded) {
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                addToCartBtn.style.backgroundColor = ''; // Reset to default
            } else {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = "Insufficient Points";
                addToCartBtn.title = `You need ${voucherPointsNeeded} points`;
            }
        }
    }

    // Function to add product to cart (now interacts with backend)
    async function addToCart(product) {
        const userId = localStorage.getItem('userId');
        if (!userId) {
            alert('Please log in to add items to your cart.');
            window.location.href = 'LoginPage.html'; // Redirect to login page
            return false; // Indicate failure
        }

        const quantity = parseInt(quantityInput.value); // Get selected quantity
        if (isNaN(quantity) || quantity < 1) {
            alert('Please enter a valid quantity.');
            return false; // Indicate failure
        }

        // Check points again before adding to cart (client-side validation)
        const userAvailablePoints = parseInt(localStorage.getItem('userPoints') || '0');
        const voucherPointsNeeded = product.points * quantity; // Total points needed

        if (userAvailablePoints < voucherPointsNeeded) {
            alert('You do not have enough points to add this voucher to your cart. Total points needed: ' + voucherPointsNeeded);
            return false; // Indicate failure
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
                    quantity: quantity // Send selected quantity
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                window.dispatchEvent(new Event('cartUpdated')); // Notify global cart count
                return true; // Indicate success
            } else {
                alert(`Failed to add to cart: ${data.message}`);
                console.error('Add to Cart Error:', data.message);
                return false; // Indicate failure
            }
        } catch (error) {
            alert('An error occurred while adding to cart.');
            console.error('Network error or failed to parse JSON:', error);
            return false; // Indicate failure
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
                // Restore product-specific points display (do NOT overwrite header user points)
                const productPointsEl = document.getElementById('product-points');
                if (productPointsEl) {
                    productPointsEl.textContent = `${currentProduct.points} Points`;
                }

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

                // Initial check for points and set button states after product details are loaded
                checkPointsAndSetButtonStates();

                // Listen for custom event that signals user points might have changed (e.g., from userSession.js)
                window.addEventListener('userPointsUpdated', checkPointsAndSetButtonStates);

                // Add to Cart button functionality
                if (addToCartBtn) {
                    addToCartBtn.addEventListener('click', () => {
                        addToCart(currentProduct);
                    });
                }

                // Redeem Now button functionality - navigates to shopping cart
                if (redeemNowBtn) {
                    redeemNowBtn.addEventListener('click', async () => {
                        // First, add the product to the cart
                        const addSuccess = await addToCart(currentProduct); // addToCart now returns a boolean for success
                        if (addSuccess) {
                            window.location.href = 'ShoppingCart.html';
                        }
                    });
                }

                // Quantity selector functionality
                if (minusBtn && plusBtn && quantityInput) {
                    minusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(quantityInput.value);
                        if (currentValue > 1) {
                            quantityInput.value = currentValue - 1;
                        }
                    });

                    plusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(quantityInput.value);
                        quantityInput.value = currentValue + 1;
                    });

                    quantityInput.addEventListener('change', () => {
                        let currentValue = parseInt(quantityInput.value);
                        if (isNaN(currentValue) || currentValue < 1) {
                            quantityInput.value = 1;
                        }
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