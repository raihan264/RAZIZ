const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Product = sequelize.define('Product', {
    name: {
        type: DataTypes.STRING,
        allowNull: false
    },
    description: {
        type: DataTypes.TEXT,
        allowNull: true
    },
    price: {
        type: DataTypes.INTEGER,
        allowNull: false
    },
    memory: { // MB
        type: DataTypes.INTEGER,
        allowNull: false
    },
    disk: { // MB
        type: DataTypes.INTEGER,
        allowNull: false
    },
    cpu: { // %
        type: DataTypes.INTEGER,
        allowNull: false
    },
    swap: {
        type: DataTypes.INTEGER,
        defaultValue: 0
    },
    io: {
        type: DataTypes.INTEGER,
        defaultValue: 500
    },
    nest_id: {
        type: DataTypes.INTEGER,
        allowNull: false
    },
    egg_id: {
        type: DataTypes.INTEGER,
        allowNull: false
    },
    docker_image: {
        type: DataTypes.STRING,
        defaultValue: 'ghcr.io/pterodactyl/yolks:java_17'
    },
    startup: {
        type: DataTypes.STRING,
        defaultValue: 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar'
    }
});

module.exports = Product;
