const cron = require('node-cron');
const { Order, Setting } = require('../models');
const pteroService = require('../services/ptero');
const { Op } = require('sequelize');

function startScheduler() {
    // Run every day at midnight (00:00)
    cron.schedule('0 0 * * *', async () => {
        console.log('Running daily server pruning task...');

        try {
            const expiredOrders = await Order.findAll({
                where: {
                    status: 'active',
                    expires_at: {
                        [Op.lt]: new Date() // expired before now
                    }
                }
            });

            console.log(`Found ${expiredOrders.length} expired servers.`);

            for (const order of expiredOrders) {
                try {
                    console.log(`Deleting server ${order.ptero_server_id} for order #${order.id}`);

                    // Call Pterodactyl API
                    await pteroService.deleteServer(order.ptero_server_id);

                    // Update DB
                    order.status = 'expired';
                    await order.save();

                    console.log(`Successfully deleted server for order #${order.id}`);
                } catch (err) {
                    console.error(`Failed to delete server for order #${order.id}:`, err.message);
                }
            }
        } catch (error) {
            console.error('Error in scheduler:', error);
        }
    });

    console.log('Scheduler started: Daily prune check at 00:00');
}

module.exports = startScheduler;
