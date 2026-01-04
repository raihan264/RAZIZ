const axios = require('axios');
const { spawn } = require('child_process');

async function runSmokeTest() {
    console.log('Starting server for smoke test...');
    const server = spawn('node', ['app.js'], {
        detached: true,
        stdio: 'ignore'
    });

    // Give it time to start
    await new Promise(resolve => setTimeout(resolve, 3000));

    try {
        console.log('Testing GET / ...');
        const response = await axios.get('http://localhost:3000');

        if (response.status === 200) {
            console.log('✅ GET / passed (Status 200)');
        } else {
            console.error(`❌ GET / failed with status ${response.status}`);
            process.exit(1);
        }

        console.log('Testing GET /login ...');
        const loginRes = await axios.get('http://localhost:3000/login');
        if (loginRes.status === 200) {
            console.log('✅ GET /login passed');
        } else {
             console.error(`❌ GET /login failed`);
             process.exit(1);
        }

    } catch (error) {
        console.error('❌ Smoke test failed:', error.message);
        process.exit(1);
    } finally {
        console.log('Stopping server...');
        try {
            process.kill(-server.pid);
        } catch (e) {
            server.kill();
        }
    }
}

runSmokeTest();
