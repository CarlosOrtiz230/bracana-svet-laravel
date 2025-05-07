// test_vulnerabilities.js

const fs = require('fs');
const child_process = require('child_process');
const crypto = require('crypto');
const express = require('express');
const bodyParser = require('body-parser');
const xml2js = require('xml2js');

const app = express();
app.use(bodyParser.json());

// 1. Eval-based RCE
function unsafeEval(userInput) {
  return eval(userInput);
}

// 2. Exec with unsanitized input
function listDir(dir) {
  child_process.exec(`ls ${dir}`, (err, stdout) => {
    console.log(stdout);
  });
}

// 3. Shell injection via execSync
function pingHost(host) {
  child_process.execSync(`ping -c 1 ${host}`, { stdio: 'inherit' });
}

// 4. Prototype pollution
function setProp(obj, key, value) {
  obj[key] = value;        // no check against "__proto__"
}

// 5. XSS: unescaped user input in HTML
app.get('/greet', (req, res) => {
  const name = req.query.name || 'guest';
  res.send(`<h1>Hello, ${name}!</h1>`);
});

// 6. Hardcoded credentials
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'P@ssw0rd!';

// 7. Weak hashing (MD5)
function hashPassword(pw) {
  return crypto.createHash('md5').update(pw).digest('hex');
}

// 8. Insecure JWT secret
const jwtSecret = 'supersecretkey123';  // should come from env

// 9. XML External Entity (XXE)
app.post('/upload-xml', (req, res) => {
  const parser = new xml2js.Parser({                          // default allows XXE
    explicitArray: false
  });
  parser.parseString(req.body.xml, (err, result) => {
    res.json(result);
  });
});

// 10. Path traversal via fs.readFile
app.get('/read', (req, res) => {
  const file = req.query.file;  
  fs.readFile(`/var/data/${file}`, 'utf8', (err, data) => {
    res.send(err ? err.message : data);
  });
});

app.listen(3000, () => console.log('JS app listening on 3000'));

