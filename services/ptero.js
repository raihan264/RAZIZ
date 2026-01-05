const axios = require('axios');
const crypto = require('crypto');
const { Setting, User } = require('../models');
const pteroConfig = require('../config/ptero');

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

    async getEggDetails(nestId, eggId) {
        if (!this.url) await this.init();
        try {
            const response = await axios.get(`${this.url}/api/application/nests/${nestId}/eggs/${eggId}?include=variables`, { headers: this.headers });
            return response.data.attributes;
        } catch (error) {
            console.error('Error fetching Egg details:', error.response ? error.response.data : error.message);
            throw error;
        }
    }

    async getFreeAllocation(nodeId) {
        if (!this.url) await this.init();
        try {
            // Fetch allocations for the node.
            // Note: In a large system you'd want to paginate, but we assume a standard setup for now.
            const response = await axios.get(`${this.url}/api/application/nodes/${nodeId}/allocations?per_page=100`, { headers: this.headers });
            const allocations = response.data.data;

            // Find one that is not assigned
            const freeAllocation = allocations.find(a => !a.attributes.assigned);

            if (!freeAllocation) {
                throw new Error(`No free allocations available on Node ${nodeId}`);
            }

            return freeAllocation.attributes.id;
        } catch (error) {
            console.error('Error fetching allocations:', error.response ? error.response.data : error.message);
            throw error;
        }
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
            const randomPassword = crypto.randomBytes(12).toString('hex') + 'Aa1!';
            // Construct email using the defined domain
            const pteroEmail = `${localUser.username}@${pteroConfig.EMAIL_DOMAIN}`;

            const payload = {
                email: pteroEmail,
                username: localUser.username,
                first_name: localUser.username,
                last_name: localUser.username,
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
            // 1. Get Egg Details (Variables)
            // Use defaults from config
            const nestId = pteroConfig.NEST_ID;
            const eggId = pteroConfig.EGG_ID;

            // Note: product.nest_id and product.egg_id in database might differ,
            // but requirements say to use these defaults.
            // If product has valid ones, we could use them, but adhering to the "Fixed Nest/Egg" rule.

            // Fetch Egg to get environment variables
            let eggVars = {};
            try {
                const response = await axios.get(`${this.url}/api/application/nests/${nestId}/eggs/${eggId}?include=variables`, { headers: this.headers });
                const variables = response.data.attributes.relationships.variables.data;
                variables.forEach(v => {
                    eggVars[v.attributes.env_variable] = v.attributes.default_value;
                });
            } catch (e) {
                console.warn('Could not fetch egg variables, using defaults if any');
            }

            // 2. Get Free Allocation
            const allocationId = await this.getFreeAllocation(pteroConfig.NODE_ID);

            // 3. Prepare Environment
            // Merge defaults with dynamic values
            const environment = {
                ...eggVars,
                SERVER_MEMORY: product.memory,
                SERVER_PORT: 25565, // This is just a placeholder, the allocationId determines the real port
            };

            const payload = {
                name: `${product.name}-${pteroUserId}`,
                user: pteroUserId,
                nest: nestId,
                egg: eggId,
                docker_image: product.docker_image,
                startup: product.startup,
                environment: environment,
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
                    default: allocationId
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
