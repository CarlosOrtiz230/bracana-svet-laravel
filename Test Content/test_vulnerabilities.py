# test_vulnerabilities.py

import os
import subprocess
import sqlite3
import pickle
import random
import hashlib
from flask import Flask, request

# 1. Eval-based RCE
def insecure_eval(user_input):
    # unsafely evaluating arbitrary Python code
    return eval(user_input)


# 2. Exec-based RCE
def insecure_exec(code):
    # executes arbitrary code strings
    exec(code)


# 3. Shell injection via os.system()
def list_files(path):
    # concatenation allows injection (e.g. path="; rm -rf /")
    os.system("ls " + path)


# 4. Subprocess with shell=True
def ping_host(host):
    # shell=True + string concat → command injection
    subprocess.call("ping -c 1 " + host, shell=True)


# 5. SQL Injection
def get_user(username):
    conn = sqlite3.connect('users.db')
    cursor = conn.cursor()
    # f-string without parameterization is injectable
    query = f"SELECT * FROM users WHERE name = '{username}'"
    cursor.execute(query)
    return cursor.fetchall()


# 6. Hardcoded credentials
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "P@ssw0rd123"   # should never live in source


# 7. Weak hashing (MD5)
def hash_password(password):
    # MD5 is considered cryptographically broken
    return hashlib.md5(password.encode()).hexdigest()


# 8. Predictable “random” tokens
def generate_token():
    # random.randint is not secure for tokens
    return str(random.randint(1000, 9999))


# 9. Insecure deserialization
def load_session(data):
    # pickle.loads can execute arbitrary object constructors
    return pickle.loads(data)


# 10. Path traversal
def read_file(filename):
    # concatenation + no sanitization → attacker can use “../” to escape
    with open('/var/www/data/' + filename, 'r') as f:
        return f.read()


# --- minimal Flask app exposing one of the above flaws ---
app = Flask(__name__)
app.debug = True   # debug mode exposes internals

@app.route('/download')
def download():
    # uses read_file without validation → path traversal
    filename = request.args.get('file', '')
    return read_file(filename)

if __name__ == '__main__':
    # runs on all interfaces in debug mode
    app.run(host='0.0.0.0', port=5000)

