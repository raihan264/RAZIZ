const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const { User } = require('../models');

// Register View
router.get('/register', (req, res) => {
    res.render('register');
});

// Register Logic
router.post('/register', async (req, res) => {
    try {
        const { username, email, password } = req.body;
        const hashedPassword = await bcrypt.hash(password, 10);

        await User.create({
            username,
            email,
            password: hashedPassword,
            isAdmin: false // Default to false
        });

        res.redirect('/login');
    } catch (error) {
        console.error(error);
        res.status(500).send('Error registering user: ' + error.message);
    }
});

// Login View
router.get('/login', (req, res) => {
    res.render('login');
});

// Login Logic
router.post('/login', async (req, res) => {
    try {
        const { email, password } = req.body;
        const user = await User.findOne({ where: { email } });

        if (user && await bcrypt.compare(password, user.password)) {
            req.session.userId = user.id;

            if (user.isAdmin) {
                return res.redirect('/admin/dashboard');
            }
            return res.redirect('/');
        }
        res.send('Invalid credentials');
    } catch (error) {
        console.error(error);
        res.status(500).send('Login error');
    }
});

// Logout
router.get('/logout', (req, res) => {
    req.session.destroy(() => {
        res.redirect('/login');
    });
});

module.exports = router;
