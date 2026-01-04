const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Order = sequelize.define('Order', {
    status: {
        type: DataTypes.ENUM('pending', 'active', 'expired'),
        defaultValue: 'pending'
    },
    ptero_server_id: {
        type: DataTypes.INTEGER,
        allowNull: true
    },
    ptero_identifier: {
        type: DataTypes.STRING,
        allowNull: true
    },
    expires_at: {
        type: DataTypes.DATE,
        allowNull: true
    }
});

module.exports = Order;
