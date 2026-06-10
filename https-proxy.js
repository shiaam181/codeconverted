const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

const options = {
    key: fs.readFileSync(path.join(__dirname, 'server.key')),
    cert: fs.readFileSync(path.join(__dirname, 'server.crt'))
};

const PROXY_PORT = 8443;
const PHP_PORT = 8000;

const server = https.createServer(options, (req, res) => {
    const proxyReq = http.request({
        hostname: '127.0.0.1',
        port: PHP_PORT,
        path: req.url,
        method: req.method,
        headers: req.headers
    }, (proxyRes) => {
        res.writeHead(proxyRes.statusCode, proxyRes.headers);
        proxyRes.pipe(res);
    });

    proxyReq.on('error', (e) => {
        res.writeHead(502);
        res.end('PHP server not reachable');
    });

    req.pipe(proxyReq);
});

server.listen(PROXY_PORT, '0.0.0.0', () => {
    console.log(`HTTPS proxy running on https://0.0.0.0:${PROXY_PORT}`);
    console.log(`Access from phone: https://192.168.1.3:${PROXY_PORT}`);
    console.log(`(Accept the self-signed cert warning on first visit)`);
});
