const express = require('express');
const router = express.Router();
const { Product, Order, Setting } = require('../models');
const { isAuthenticated } = require('../middleware/auth');

// Product Details
router.get('/product/:id', async (req, res) => {
    try {
        const product = await Product.findByPk(req.params.id);
        if (!product) return res.status(404).send('Product not found');
        res.render('product', { product, user: res.locals.user });
    } catch (error) {
        res.status(500).send('Error');
    }
});

// Create Order (Checkout)
router.post('/order/:id', isAuthenticated, async (req, res) => {
    try {
        const product = await Product.findByPk(req.params.id);
        if (!product) return res.status(404).send('Product not found');

        // Create Pending Order
        const order = await Order.create({
            user_id: req.session.userId,
            product_id: product.id,
            status: 'pending'
        });

        // Get Admin Phone
        const phoneSetting = await Setting.findOne({ where: { key: 'admin_phone' } });
        const adminPhone = phoneSetting ? phoneSetting.value : '628123456789'; // Fallback

        // Prepare WhatsApp Message
        const message = `Halo Admin, saya ingin membeli server:
- Produk: ${product.name}
- Harga: Rp ${product.price}
- Order ID: #${order.id}
- User Email: ${res.locals.user.email}

Mohon diproses. Terima kasih.`;

        const waUrl = `https://wa.me/${adminPhone}?text=${encodeURIComponent(message)}`;

        // Redirect
        res.redirect(waUrl);

    } catch (error) {
        console.error(error);
        res.status(500).send('Error processing order');
    }
});

module.exports = router;
