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

const pteroConfig = require('../config/ptero');

// Server Management
router.get('/server/create', async (req, res) => {
    const users = await User.findAll();
    const products = await Product.findAll();
    res.render('admin/server_create', { users, products });
});

router.post('/server/create', async (req, res) => {
    try {
        const { user_id, product_id } = req.body;
        const user = await User.findByPk(user_id);
        const product = await Product.findByPk(product_id);

        if (!user || !product) {
            return res.status(404).send('User or Product not found');
        }

        // 1. Check/Create Ptero User
        // Use the new naming convention logic in PteroService
        const pteroEmail = `${user.username}@${pteroConfig.EMAIL_DOMAIN}`;
        let pteroUser = await pteroService.getPteroUser(pteroEmail);

        if (!pteroUser) {
            // If not found by constructed email, try checking by local email just in case?
            // The requirement says "email buat username+raziz.my.id", so we stick to that.
            // If the user already exists with that email, getPteroUser returns it.
            // If not, create it.
            pteroUser = await pteroService.createPteroUser(user);
        }

        // 2. Create Ptero Server
        const server = await pteroService.createServer(pteroUser.id, product);

        // 3. Create Local Order Record (Active)
        const expiresAt = new Date();
        expiresAt.setDate(expiresAt.getDate() + 30); // Default 30 days

        await Order.create({
            user_id: user.id,
            product_id: product.id,
            status: 'active',
            ptero_server_id: server.id,
            ptero_identifier: server.uuid,
            expires_at: expiresAt
        });

        res.redirect('/admin/orders'); // Redirect to orders list to see the new server

    } catch (error) {
        console.error('Create Server Error:', error);
        res.status(500).send('Error creating server: ' + error.message);
    }
});

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
