const express = require('express');
const app = express();
const path = require('path');
const session = require('express-session');
const SQLiteStore = require('connect-sqlite3')(session);
require('dotenv').config();

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));
app.set('view engine', 'ejs');

// Session Config
app.use(session({
    store: new SQLiteStore({ dir: '.', db: 'sessions.sqlite' }),
    secret: process.env.SESSION_SECRET || 'secret_key_change_me',
    resave: false,
    saveUninitialized: false,
    cookie: { maxAge: 7 * 24 * 60 * 60 * 1000 } // 1 week
}));

// Import Routes
const authRoutes = require('./routes/auth');
const adminRoutes = require('./routes/admin');
const shopRoutes = require('./routes/shop');
const dashboardRoutes = require('./routes/dashboard');
const { sequelize, User, Product } = require('./models');
const bcrypt = require('bcryptjs');

// Make user available in all views
app.use(async (req, res, next) => {
    res.locals.user = null;
    if (req.session.userId) {
        const user = await User.findByPk(req.session.userId);
        if (user) {
            res.locals.user = user;
        }
    }
    next();
});

app.use('/', authRoutes);
app.use('/admin', adminRoutes);
app.use('/', shopRoutes);
app.use('/', dashboardRoutes);

// Home Route
app.get('/', async (req, res) => {
    const products = await Product.findAll();
    res.render('index', { user: res.locals.user, products });
});

const PORT = process.env.PORT || 3000;

async function startServer() {
    try {
        await sequelize.sync({ alter: true });
        console.log('Database synced.');

        // Admin Seeding
        const userCount = await User.count();
        if (userCount === 0) {
            const hashedPassword = await bcrypt.hash('admin123', 10);
            await User.create({
                username: 'admin',
                email: 'admin@example.com',
                password: hashedPassword,
                isAdmin: true
            });
            console.log('Default Admin created: admin@example.com / admin123');
        }

        app.listen(PORT, () => {
            console.log(`Server running on port ${PORT}`);

            // Start Scheduler
            const startScheduler = require('./cron/scheduler');
            startScheduler();
        });
    } catch (error) {
        console.error('Failed to start server:', error);
    }
}

startServer();
