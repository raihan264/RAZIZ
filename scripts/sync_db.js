const { sequelize } = require('../models');

async function sync() {
    try {
        await sequelize.sync({ alter: true }); // Use alter to update schema if changed
        console.log('Database synced successfully.');
    } catch (error) {
        console.error('Error syncing database:', error);
    }
}

sync();
