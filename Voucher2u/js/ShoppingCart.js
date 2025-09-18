// ShoppingCart.js

document.addEventListener('DOMContentLoaded', function() {
    const cartTable = document.querySelector('.cart-table tbody');
    const subtotalValue = document.getElementById('subtotal-value');
    const grandtotalValue = document.getElementById('grand-total-value');
    // Assuming sales tax is always $0 for now based on the image
    // const salesTaxValue = document.getElementById('sales-tax-value'); 

    const userId = localStorage.getItem('userId');
    if (!userId) {
        // If user is not logged in, redirect to login page or show a message
        console.warn('User not logged in. Redirecting to login page.');
        // window.location.href = 'LoginPage.html'; 
        cartTable.innerHTML = '<tr><td colspan="4">Please log in to view your cart.</td></tr>';
        subtotalValue.textContent = 0;
        grandtotalValue.textContent = 0;
        window.dispatchEvent(new Event('cartUpdated')); // Update global cart count to 0
        return; // Stop further execution if no user ID
    }

    async function fetchAndRenderCartItems() {
        try {
            const response = await fetch(`../php/fetch_cart_items.php?user_id=${userId}`);
            const data = await response.json();

            if (data.success) {
                let subtotal = 0;
                cartTable.innerHTML = ''; // Clear existing items

                if (data.cart_items.length > 0) {
                    data.cart_items.forEach(item => {
                        const itemTotal = item.quantity * item.points;
                        subtotal += itemTotal;

                        const tr = document.createElement('tr');
                        tr.classList.add('cart-item');
                        tr.dataset.voucherId = item.voucher_id; // Store voucher ID
                        tr.innerHTML = `
                            <td class="item-details">
                                <div class="item-image-wrapper">
                                    <img src="${item.image}" alt="${item.title}">
                                    <button class="remove-item" data-voucher-id="${item.voucher_id}"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="item-info">
                                    <h3>${item.title}</h3>
                                    <p>${item.description}</p>
                                </div>
                            </td>
                            <td class="item-points">
                                <span class="points-value">${item.points}</span>
                                <p class="discount">Discount : 20%</p>
                            </td>
                            <td class="item-quantity">
                                <div class="quantity-control">
                                    <button class="quantity-minus" data-voucher-id="${item.voucher_id}">-</button>
                                    <input type="number" value="${item.quantity}" min="1" class="quantity-input" data-voucher-id="${item.voucher_id}">
                                    <button class="quantity-plus" data-voucher-id="${item.voucher_id}">+</button>
                                </div>
                            </td>
                            <td class="item-total-points">${itemTotal}</td>
                        `;
                        cartTable.appendChild(tr);
                    });
                } else {
                    cartTable.innerHTML = '<tr><td colspan="4">Your cart is empty.</td></tr>';
                }

                subtotalValue.textContent = subtotal;
                grandtotalValue.textContent = subtotal; // Assuming no sales tax for now
                window.dispatchEvent(new Event('cartUpdated')); // Notify global cart count
            } else {
                console.error('Error fetching cart items:', data.message);
                cartTable.innerHTML = `<tr><td colspan="4">Error loading cart: ${data.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Network error or failed to parse JSON for cart items:', error);
            cartTable.innerHTML = '<tr><td colspan="4">Failed to load cart items. Please try again later.</td></tr>';
        }
    }

    // Handle quantity changes and item removal
    cartTable.addEventListener('click', async function(event) {
        const target = event.target;
        const voucherId = target.dataset.voucherId || target.closest('[data-voucher-id]')?.dataset.voucherId;

        if (!voucherId) return;

        let currentQuantity = parseInt(target.closest('.cart-item').querySelector('.quantity-input').value);
        let newQuantity = currentQuantity;
        let action = '';

        if (target.classList.contains('quantity-plus')) {
            newQuantity += 1;
            action = 'update';
        } else if (target.classList.contains('quantity-minus')) {
            if (currentQuantity > 1) {
                newQuantity -= 1;
                action = 'update';
            }
        } else if (target.closest('.remove-item')) {
            action = 'delete';
        }

        try {
            let response;
            if (action === 'update') {
                response = await fetch('../php/update_cart_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ voucher_id: voucherId, user_id: userId, quantity: newQuantity })
                });
            } else if (action === 'delete') {
                response = await fetch('../php/delete_cart_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ voucher_id: voucherId, user_id: userId })
                });
            }

            if (response) {
                const data = await response.json();
                if (data.success) {
                    fetchAndRenderCartItems(); // Re-fetch and re-render cart after successful operation
                } else {
                    alert(`Failed to update cart: ${data.message}`);
                    console.error('Cart Update Error:', data.message);
                }
            }
        } catch (error) {
            alert('An error occurred while updating the cart.');
            console.error('Network error during cart update:', error);
        }
    });

    // Initial display of cart items and totals
    fetchAndRenderCartItems();

    // Listen for global cart updates (e.g., from ProductDetails.js)
    window.addEventListener('cartUpdated', fetchAndRenderCartItems);
});

