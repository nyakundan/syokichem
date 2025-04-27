// AJAX Add to Cart for Special Offers Page

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.offers-list .add-to-cart-form').forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.add-to-cart-btn');
            const originalContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            try {
                const response = await fetch('components/cart_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showToast(result.message || 'Product added to cart!', 'success');
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                } else {
                    showToast(result.message || 'Failed to add product!', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            }
        });
    });
});

// Toast notification (reuse global if available)
if (typeof showToast !== 'function') {
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Update cart count (reuse global if available)
if (typeof updateCartCount !== 'function') {
    function updateCartCount() {
        fetch('components/get_cart_count.php')
            .then(response => response.text())
            .then(count => {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = count;
                    cartCount.classList.add('pulse');
                    setTimeout(() => cartCount.classList.remove('pulse'), 500);
                }
            })
            .catch(error => {
                console.error('Error updating cart count:', error);
            });
    }
}
