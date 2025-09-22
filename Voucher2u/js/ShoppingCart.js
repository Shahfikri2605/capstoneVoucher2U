// ShoppingCart.js

document.addEventListener('DOMContentLoaded', function() {
    const cartTable = document.querySelector('.cart-table tbody');
    const subtotalValue = document.getElementById('subtotal-value');
    const grandtotalValue = document.getElementById('grand-total-value');
    const pointsDeductionValue = document.getElementById('points-deduction-value');
    const finalTotalValue = document.getElementById('final-total-value');
    const pointsErrorMessage = document.getElementById('points-error-message');
    const checkoutBtn = document.querySelector('.checkout-btn');
    const clearCartBtn = document.querySelector('.clear-cart-btn'); // Get the new clear cart button
    const redemptionSuccessModal = document.getElementById('redemption-success-modal');
    const redemptionModalClose = document.getElementById('redemption-modal-close');
    const newPointsBalanceSpan = document.getElementById('new-points-balance');
    const downloadVoucherBtn = document.getElementById('download-voucher-btn');
    let selectedVouchers = new Set(); // To store selected voucher_ids
    let isInitialLoad = true; // Flag to track initial cart load
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
                // let subtotal = 0; // No longer calculate subtotal here
                cartTable.innerHTML = ''; // Clear existing items

                if (data.cart_items.length > 0) {
                    // On initial load, or subsequent re-renders, checkboxes should start unchecked.
                    // selectedVouchers will be updated only by user interaction.
                    // It's important to clear selectedVouchers only if we want all checkboxes to be unticked on re-render, 
                    // or manage its state carefully to preserve user selections across re-renders.
                    // For now, we want all items unticked on initial load/re-render, so we clear it.
                    selectedVouchers.clear(); 

                    data.cart_items.forEach(item => {
                        const isSelected = false; // Always false on render, user will tick it
                        const checkedAttribute = ''; // No 'checked' attribute

                        if (isSelected) {
                            selectedVouchers.add(item.voucher_id); // Re-add if it was previously selected
                        }
                        const tr = document.createElement('tr');
                        tr.classList.add('cart-item');
                        tr.dataset.voucherId = item.voucher_id; // Store voucher ID
                        tr.innerHTML = `
                            <td>
                                <input type="checkbox" class="item-select-checkbox" data-voucher-id="${item.voucher_id}" ${checkedAttribute}>
                            </td>
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
                            <td class="item-total-points">${item.quantity * item.points}</td>
                        `;
                        cartTable.appendChild(tr);
                    });

                    // After rendering, ensure selectedVouchers accurately reflects current checkbox states
                    // selectedVouchers.clear(); // Clear the set to rebuild it
                    // document.querySelectorAll('.item-select-checkbox').forEach(checkbox => {
                    //     if (checkbox.checked) {
                    //         selectedVouchers.add(checkbox.dataset.voucherId);
                    //     }
                    // });

                } else {
                    cartTable.innerHTML = '<tr><td colspan="5">Your cart is empty.</td></tr>'; // colspan changed to 5
                }
                updateCartTotals(); // Call new function to calculate and update totals
                window.dispatchEvent(new Event('cartUpdated'));
                isInitialLoad = false; // Set to false after initial load
            } else {
                console.error('Error fetching cart items:', data.message);
                cartTable.innerHTML = `<tr><td colspan="4">Error loading cart: ${data.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Network error or failed to parse JSON for cart items:', error);
            cartTable.innerHTML = '<tr><td colspan="4">Failed to load cart items. Please try again later.</td></tr>';
        }
    }

    function updateCartTotals() {
        let currentSubtotal = 0;
        // selectedVouchers.clear(); // Removed: selectedVouchers is managed by checkbox click handler

        document.querySelectorAll('.cart-item').forEach(itemRow => {
            const checkbox = itemRow.querySelector('.item-select-checkbox');
            if (checkbox && checkbox.checked) {
                const quantity = parseInt(itemRow.querySelector('.quantity-input').value);
                const points = parseInt(itemRow.querySelector('.points-value').textContent);
                currentSubtotal += quantity * points;
            }
        });

        subtotalValue.textContent = currentSubtotal;

        const userPoints = parseInt(localStorage.getItem('userPoints')) || 0;
        let pointsDeduction = 0;

        if (userPoints > 0 && currentSubtotal > 0) {
            pointsDeduction = Math.min(userPoints, currentSubtotal);
        }

        const finalTotal = currentSubtotal - pointsDeduction;

        pointsDeductionValue.textContent = pointsDeduction;
        finalTotalValue.textContent = finalTotal;
        grandtotalValue.textContent = finalTotal; // Update grand total display as well

        const anyCheckboxChecked = document.querySelectorAll('.item-select-checkbox:checked').length > 0;

        if (userPoints < currentSubtotal) {
            pointsErrorMessage.style.display = 'block';
            checkoutBtn.disabled = true;
            pointsDeductionValue.style.color = 'red'; // Highlight points deduction in red
        } else {
            pointsErrorMessage.style.display = 'none';
            checkoutBtn.disabled = !anyCheckboxChecked;
            pointsDeductionValue.style.color = 'inherit'; // Reset color
        }

        // Enable/disable checkout button based on whether any checkbox is currently checked
        // The above logic already handles disabling based on points, so this is for when points are sufficient
        // checkoutBtn.disabled = !anyCheckboxChecked;
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
        } else if (target.classList.contains('item-select-checkbox')) {
            // Handle checkbox change
            if (target.checked) {
                selectedVouchers.add(voucherId);
            } else {
                selectedVouchers.delete(voucherId);
            }
            updateCartTotals();
            return; // No backend update needed for checkbox toggle
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

    // Handle checkout button click
    checkoutBtn.addEventListener('click', async function() {
        if (selectedVouchers.size === 0) {
            alert('Please select at least one voucher to checkout.');
            return;
        }

        const itemsToCheckout = [];
        document.querySelectorAll('.cart-item').forEach(itemRow => {
            const voucherId = itemRow.dataset.voucherId;
            if (selectedVouchers.has(voucherId)) {
                const quantity = parseInt(itemRow.querySelector('.quantity-input').value);
                itemsToCheckout.push({ voucher_id: voucherId, quantity: quantity });
            }
        });

        try {
            const response = await fetch('../php/checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, items: itemsToCheckout })
            });
            const data = await response.json();

            if (data.success) {
                // alert('Checkout successful!');
                newPointsBalanceSpan.textContent = data.newPointsBalance; // Assuming backend returns newPointsBalance
                redemptionSuccessModal.style.display = 'flex'; // Show the modal

                // Store redeemed vouchers data to be used by the download button
                downloadVoucherBtn.dataset.redeemedVouchers = JSON.stringify(data.redeemedVouchers || []);

                fetchAndRenderCartItems(); // Re-render cart after checkout
            } else {
                alert(`Redemption failed: ${data.message}`);
                console.error('Redemption Error:', data.message);
            }
        } catch (error) {
            alert('An error occurred during checkout.');
            console.error('Network error during checkout:', error);
        }
    });

    // Handle clear cart button click
    clearCartBtn.addEventListener('click', async function() {
        if (showCustomConfirm('Are you sure you want to clear your cart?')) {
            try {
                const response = await fetch('../php/clear_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const data = await response.json();

                if (data.success) {
                    alert('Your cart has been cleared!');
                    selectedVouchers.clear(); // Clear selected vouchers set
                    fetchAndRenderCartItems(); // Re-render cart after clearing
                } else {
                    alert(`Failed to clear cart: ${data.message}`);
                    console.error('Clear Cart Error:', data.message);
                }
            } catch (error) {
                alert('An error occurred while clearing the cart.');
                console.error('Network error during clear cart:', error);
            }
        }
    });

    // Initial display of cart items and totals
    fetchAndRenderCartItems();

    // Handle modal close button
    redemptionModalClose.addEventListener('click', () => {
        redemptionSuccessModal.style.display = 'none';
    });

    // Handle download voucher button click
    downloadVoucherBtn.addEventListener('click', async () => {
        const redeemedVouchersData = downloadVoucherBtn.dataset.redeemedVouchers;
        if (!redeemedVouchersData) {
            alert('No voucher data available for download.');
            return;
        }

        const redeemedVouchers = JSON.parse(redeemedVouchersData);

        try {
            const response = await fetch('../php/generate_voucher_pdf.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ redeemedVouchers: redeemedVouchers, newPointsBalance: newPointsBalanceSpan.textContent })
            });

            if (response.ok) {
                // It's a file download, so we get the blob and create a URL
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'vouchers_redeemed.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } else {
                const errorData = await response.json();
                alert(`Failed to generate PDF: ${errorData.message}`);
                console.error('PDF Generation Error:', errorData.message);
            }
        } catch (error) {
            alert('An error occurred during PDF generation.');
            console.error('Network error during PDF generation:', error);
        }
    });

    // Custom confirmation function (can be upgraded to a custom modal later)
    function showCustomConfirm(message) {
        return confirm(message);
    }

    // Listen for global cart updates (e.g., from ProductDetails.js)
    // window.addEventListener('cartUpdated', fetchAndRenderCartItems);
});

