import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(process.cwd(), 'dist');
const port = Number(process.env.PORT || 4173);
const types = {'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.json':'application/json; charset=utf-8','.xml':'application/xml; charset=utf-8','.txt':'text/plain; charset=utf-8','.svg':'image/svg+xml'};

const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);
  let pathname = decodeURIComponent(url.pathname);
  let file = path.join(root, pathname);
  if (!path.extname(file)) file = path.join(file, 'index.html');
  if (!fs.existsSync(file) || !fs.statSync(file).isFile()) file = path.join(root, '404.html');
  const ext = path.extname(file);
  res.writeHead(file.endsWith('404.html') ? 404 : 200, {'Content-Type': types[ext] || 'application/octet-stream', 'Cache-Control':'no-cache'});
  fs.createReadStream(file).pipe(res);
});

server.listen(port, () => console.log(`버려줘 dev server → http://localhost:${port}`));
