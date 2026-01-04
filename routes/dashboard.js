const express = require('express');
const router = express.Router();
const { Order, Product, Setting } = require('../models');
const { isAuthenticated } = require('../middleware/auth');
const { Op } = require('sequelize');

router.get('/dashboard', isAuthenticated, async (req, res) => {
    try {
        const orders = await Order.findAll({
            where: {
                user_id: req.session.userId,
                status: 'active'
            },
            include: [Product]
        });

        const urlSetting = await Setting.findOne({ where: { key: 'ptero_url' } });
        const ptero_url = urlSetting ? urlSetting.value : '#';

        res.render('dashboard', { user: res.locals.user, orders, ptero_url });
    } catch (error) {
        console.error(error);
        res.status(500).send('Error loading dashboard');
    }
});

module.exports = router;
