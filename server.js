const http = require('http');
const fs = require('fs');
const path = require('path');

const port = process.env.PORT || 3000;
const rootDir = __dirname;

const mimeTypes = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon'
};

const server = http.createServer((req, res) => {
  try {
    let reqUrl = req.url || '/';
    const parsedUrl = new URL(reqUrl, `http://${req.headers.host || 'localhost'}`);
    let pathname = parsedUrl.pathname;

    if (pathname === '/') {
      pathname = '/index.html';
    }

    const normalizedPath = path.normalize(pathname).replace(/^\/+/, '');
    const filePath = path.join(rootDir, normalizedPath);

    if (!filePath.startsWith(rootDir)) {
      throw new Error('Invalid path');
    }

    const extension = path.extname(filePath).toLowerCase();
    const contentType = mimeTypes[extension] || 'application/octet-stream';

    if (fs.existsSync(filePath) && fs.statSync(filePath).isDirectory()) {
      const directoryIndexPath = path.join(filePath, 'index.html');
      if (fs.existsSync(directoryIndexPath)) {
        serveFile(directoryIndexPath, res, contentType);
        return;
      }
    }

    if (!fs.existsSync(filePath) || !fs.statSync(filePath).isFile()) {
      res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
      res.end('404 Not Found');
      return;
    }

    serveFile(filePath, res, contentType);
  } catch (error) {
    res.writeHead(400, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('400 Bad Request');
  }
});

function serveFile(filePath, res, contentType) {
  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
      res.end('500 Internal Server Error');
      return;
    }

    res.writeHead(200, { 'Content-Type': contentType });
    res.end(data);
  });
}

server.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});
