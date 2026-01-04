const axios = require('axios');
const crypto = require('crypto');
const { Setting, User } = require('../models');

class PteroService {
    constructor() {
        this.url = null;
        this.appKey = null;
    }

    async init() {
        const urlSetting = await Setting.findOne({ where: { key: 'ptero_url' } });
        const keySetting = await Setting.findOne({ where: { key: 'ptero_app_key' } });

        if (!urlSetting || !keySetting) {
            throw new Error('Pterodactyl credentials not configured.');
        }

        this.url = urlSetting.value.replace(/\/$/, ''); // Remove trailing slash
        this.appKey = keySetting.value;
        this.headers = {
            'Authorization': `Bearer ${this.appKey}`,
            'Content-Type': 'application/json',
            'Accept': 'Application/vnd.pterodactyl.v1+json'
        };
    }

    async getPteroUser(email) {
        if (!this.url) await this.init();
        try {
            const response = await axios.get(`${this.url}/api/application/users?filter[email]=${email}`, { headers: this.headers });
            if (response.data.data.length > 0) {
                return response.data.data[0];
            }
            return null;
        } catch (error) {
            console.error('Error fetching Ptero user:', error.response ? error.response.data : error.message);
            throw error;
        }
    }

    async createPteroUser(localUser) {
        if (!this.url) await this.init();
        try {
            const randomPassword = crypto.randomBytes(8).toString('hex') + 'Aa1!';
            const payload = {
                email: localUser.email,
                username: localUser.username,
                first_name: localUser.username,
                last_name: 'User',
                password: randomPassword
            };
            const response = await axios.post(`${this.url}/api/application/users`, payload, { headers: this.headers });
            return response.data.attributes;
        } catch (error) {
            console.error('Error creating Ptero user:', error.response ? error.response.data : error.message);
            throw error;
        }
    }

    async createServer(pteroUserId, product) {
        if (!this.url) await this.init();
        try {
            const payload = {
                name: `${product.name} - ${pteroUserId}`,
                user: pteroUserId,
                egg: product.egg_id,
                docker_image: product.docker_image,
                startup: product.startup,
                environment: {
                    SERVER_MEMORY: product.memory,
                    SERVER_PORT: 25565, // This is usually assigned dynamically by allocation
                    // Additional env vars might be needed depending on the egg
                },
                limits: {
                    memory: product.memory,
                    swap: product.swap,
                    disk: product.disk,
                    io: product.io,
                    cpu: product.cpu
                },
                feature_limits: {
                    databases: 1,
                    backups: 1,
                    allocations: 1
                },
                allocation: {
                    default: 0 // Auto-assign
                }
            };

            const response = await axios.post(`${this.url}/api/application/servers`, payload, { headers: this.headers });
            return response.data.attributes;
        } catch (error) {
            console.error('Error creating Ptero server:', error.response ? error.response.data : error.message);
            throw error;
        }
    }

    async deleteServer(serverId) {
        if (!this.url) await this.init();
        try {
            // serverId is the internal ID (integer), not UUID
            await axios.delete(`${this.url}/api/application/servers/${serverId}`, { headers: this.headers });
            return true;
        } catch (error) {
            console.error('Error deleting Ptero server:', error.response ? error.response.data : error.message);
            throw error;
        }
    }
}

module.exports = new PteroService();
