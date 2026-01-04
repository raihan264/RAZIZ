const bcrypt = require('bcryptjs');

module.exports = {
    isAuthenticated: (req, res, next) => {
        if (req.session.userId) {
            return next();
        }
        res.redirect('/login');
    },

    isAdmin: async (req, res, next) => {
        if (req.session.userId) {
            const { User } = require('../models');
            const user = await User.findByPk(req.session.userId);
            if (user && user.isAdmin) {
                res.locals.user = user; // Make user available in views
                return next();
            }
        }
        res.status(403).send('Forbidden: Admins only');
    }
};
