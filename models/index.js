const sequelize = require('../config/database');
const User = require('./User');
const Product = require('./Product');
const Order = require('./Order');
const Setting = require('./Setting');

// Associations
User.hasMany(Order, { foreignKey: 'user_id' });
Order.belongsTo(User, { foreignKey: 'user_id' });

Product.hasMany(Order, { foreignKey: 'product_id' });
Order.belongsTo(Product, { foreignKey: 'product_id' });

module.exports = {
    sequelize,
    User,
    Product,
    Order,
    Setting
};
