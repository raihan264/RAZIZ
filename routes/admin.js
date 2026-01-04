const express = require('express');
const router = express.Router();
const { Setting, Product, User, Order } = require('../models');
const { isAdmin } = require('../middleware/auth');

router.use(isAdmin);

// Dashboard
router.get('/dashboard', (req, res) => {
    res.render('admin/dashboard', { user: res.locals.user }); // You'll need a simple dashboard view or reuse header
});

// Settings
router.get('/settings', async (req, res) => {
    const settingsRaw = await Setting.findAll();
    const settings = {};
    settingsRaw.forEach(s => settings[s.key] = s.value);
    res.render('admin/settings', { settings });
});

router.post('/settings', async (req, res) => {
    const { ptero_url, ptero_app_key, ptero_client_key, admin_phone } = req.body;

    await Setting.upsert({ key: 'ptero_url', value: ptero_url });
    await Setting.upsert({ key: 'ptero_app_key', value: ptero_app_key });
    await Setting.upsert({ key: 'ptero_client_key', value: ptero_client_key });
    await Setting.upsert({ key: 'admin_phone', value: admin_phone });

    res.redirect('/admin/settings');
});

// Products
router.get('/products', async (req, res) => {
    const products = await Product.findAll();
    res.render('admin/products', { products });
});

router.get('/products/create', (req, res) => {
    res.render('admin/product_form');
});

router.post('/products', async (req, res) => {
    await Product.create(req.body);
    res.redirect('/admin/products');
});

// Users
router.get('/users', async (req, res) => {
    const users = await User.findAll();
    res.render('admin/users', { users });
});

const pteroService = require('../services/ptero');

// Orders
router.get('/orders', async (req, res) => {
    const orders = await Order.findAll({
        include: [User, Product],
        order: [['createdAt', 'DESC']]
    });
    res.render('admin/orders', { orders });
});

router.post('/orders/:id/approve', async (req, res) => {
    try {
        const order = await Order.findByPk(req.params.id, { include: [User, Product] });
        if (!order) return res.status(404).send('Order not found');
        if (order.status !== 'pending') return res.status(400).send('Order already processed');

        // 1. Check/Create Ptero User
        let pteroUser = await pteroService.getPteroUser(order.User.email);
        if (!pteroUser) {
            // Need to pass username/email details
            pteroUser = await pteroService.createPteroUser(order.User);
        }

        // 2. Create Ptero Server
        const server = await pteroService.createServer(pteroUser.id, order.Product);

        // 3. Update Order
        const expiresAt = new Date();
        expiresAt.setDate(expiresAt.getDate() + 30); // 30 days validity

        order.ptero_server_id = server.id;
        order.ptero_identifier = server.uuid; // or identifier
        order.status = 'active';
        order.expires_at = expiresAt;
        await order.save();

        res.redirect('/admin/orders');

    } catch (error) {
        console.error('Approval Error:', error);
        res.status(500).send('Error processing order: ' + error.message);
    }
});

module.exports = router;
