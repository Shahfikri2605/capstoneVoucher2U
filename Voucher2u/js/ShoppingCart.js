// ShoppingCart.js

document.addEventListener('DOMContentLoaded', function() {
    const cartTable = document.querySelector('.cart-table tbody');
    const subtotalValue = document.getElementById('subtotal-value');
    const grandtotalValue = document.getElementById('grand-total-value');
    // Assuming sales tax is always $0 for now based on the image
    // const salesTaxValue = document.getElementById('sales-tax-value'); 

    function updateCartTotals() {
        let subtotal = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const quantity = parseInt(item.querySelector('.quantity-input').value);
            const pointsPerItem = parseInt(item.querySelector('.points-value').textContent);
            const itemTotal = quantity * pointsPerItem;
            item.querySelector('.item-total-points').textContent = itemTotal;
            subtotal += itemTotal;
        });

        subtotalValue.textContent = subtotal;
        grandtotalValue.textContent = subtotal; // Assuming no sales tax for now
    }

    // Handle quantity changes
    cartTable.addEventListener('click', function(event) {
        if (event.target.classList.contains('quantity-plus')) {
            const input = event.target.previousElementSibling;
            input.value = parseInt(input.value) + 1;
            updateCartTotals();
        } else if (event.target.classList.contains('quantity-minus')) {
            const input = event.target.nextElementSibling;
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateCartTotals();
            }
        } else if (event.target.closest('.remove-item')) {
            event.target.closest('.cart-item').remove();
            updateCartTotals();
        }
    });

    // Initial calculation on page load
    updateCartTotals();
});

