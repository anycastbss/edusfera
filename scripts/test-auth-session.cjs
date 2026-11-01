const https = require('https');

const BASE_HOST = 'edusfera.by';

function request(path, options = {}, data = null) {
    return new Promise((resolve, reject) => {
        const req = https.request({
            hostname: BASE_HOST,
            port: 443,
            path: path,
            method: options.method || 'GET',
            headers: options.headers || {},
            timeout: 10000,
        }, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => resolve({
                statusCode: res.statusCode,
                headers: res.headers,
                body: body
            }));
        });
        req.on('error', reject);
        req.on('timeout', () => { req.destroy(); reject(new Error('timeout')); });
        if (data) req.write(data);
        req.end();
    });
}

async function getSessionCookie() {
    // 1. Get initial cookies
    const initRes = await request('/login');
    const setCookies = initRes.headers['set-cookie'] || [];
    let xsrfRaw = '';
    let cookies = [];

    for (const c of setCookies) {
        cookies.push(c.split(';')[0]);
        if (c.startsWith('XSRF-TOKEN=')) {
            xsrfRaw = c.split(';')[0].substring('XSRF-TOKEN='.length);
        }
    }

    const xsrfToken = decodeURIComponent(xsrfRaw);

    // 2. Login
    const postData = JSON.stringify({
        email: 'tutor-test@edusfera.by',
        password: 'Password123!',
    });

    const loginRes = await request('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(postData),
            'X-XSRF-TOKEN': xsrfToken,
            'Cookie': cookies.join('; '),
            'Accept': 'application/json',
        }
    }, postData);

    console.log('Login status:', loginRes.statusCode);
    const loginCookies = loginRes.headers['set-cookie'] || [];
    for (const c of loginCookies) {
        cookies.push(c.split(';')[0]);
    }

    // 3. Test /cabinet with session
    const cabinetRes = await request('/cabinet', {
        headers: {
            'Cookie': cookies.join('; '),
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        }
    });

    console.log('Cabinet status:', cabinetRes.statusCode, 'Body length:', cabinetRes.body.length);
    console.log('Cabinet contains InitialData:', cabinetRes.body.includes('EDUSFERA_MOBILE_INITIAL_DATA'));

    // 4. Test /m with session
    const mobileRes = await request('/m', {
        headers: {
            'Cookie': cookies.join('; '),
            'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        }
    });
    console.log('Mobile /m status:', mobileRes.statusCode, 'Body length:', mobileRes.body.length);
    console.log('Mobile contains InitialData:', mobileRes.body.includes('EDUSFERA_MOBILE_INITIAL_DATA'));
}

getSessionCookie().catch(console.error);
