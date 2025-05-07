# BRANACA Security Scanner (Laravel Backend)

This is the backend for the **BRANACA-SVET** project, a Laravel application that interfaces with security tools like OWASP ZAP, Nikto, and Semgrep inside Docker containers. It supports both **static** and **dynamic** code vulnerability scanning.
In future laravel can be turned into a backend only mode and integrate React on the frontend. The app can adapt later other analyser tools.

---

## 🚀 Project Requirements

* PHP >= 8.1
* Composer
* Laravel >= 10
* Docker & Docker Compose
* Node.js and NPM (optional, for frontend assets)
* A Unix-based OS (Linux/Mac preferred) or WSL on Windows

---

## 🔧 Installation Instructions

### 1. System Preparation (Ubuntu 24.04)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y build-essential curl wget git
```

### 2. Install Docker and Docker Compose

```bash
sudo apt install apt-transport-https ca-certificates curl software-properties-common -y
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/docker.gpg

echo "deb [arch=$(dpkg --print-architecture)] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin -y

sudo docker run hello-world
```

### 3. Install Git, Python, PostgreSQL and Utilities

```bash
sudo apt install git python3 python3-pip postgresql postgresql-contrib php-pgsql -y
```

Create a PostgreSQL user and database:

```bash
sudo -u postgres psql
CREATE USER branaca WITH PASSWORD 'secret123';
CREATE DATABASE branaca_db OWNER branaca;
\q
```

### 4. Clone and Set Up Laravel Project

```bash
mkdir ~/bracana-svet-laravel
cd ~/bracana-svet-laravel

git clone https://github.com/CarlosOrtiz230/bracana-svet-laravel
cd bracana-svet-laravel/backend
```

Install PHP dependencies:

```bash
sudo apt install composer unzip curl php-cli php-mbstring php-xml php-bcmath php-curl php-zip php-mysql php-tokenizer php-pgsql -y
composer install
```

Set up `.env` file:

```bash
cp .env.example .env
```

Update `.env`:

```env
APP_NAME=BRANACA
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
DOCKER_HOST_MODE=linux
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=branaca_db
DB_USERNAME=branaca
DB_PASSWORD=secret123
```

Generate Laravel key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

---

## 🐳 Docker Requirements

Make sure Docker is installed and the following images are pulled or built:

```bash
# Build ZAP wrapper
cd backend/zap-scanner
sudo docker build -t zap-scanner .

# Build Nikto if needed
cd ../nikto-scanner
sudo docker build -t bracana-nikto .

# Pull Semgrep image
sudo docker pull returntocorp/semgrep
```

---

## 🧪 Running the App Locally

```bash
cd backend
php artisan serve
```

Visit: [http://localhost:8000](http://localhost:8000)

---


## 📡 Testing Targets (Public IPs and Dummy Sites)

For dynamic testing, we used both dummy apps and public intentionally vulnerable targets:

### 🧪 Local Dummy Apps

- **Flask + SvelteKit Vulnerable App (locally hosted)**  
  Includes 11 known vulnerabilities such as SQLi, XSS, command injection, and missing auth.  
  Runs at:  
  - Flask backend: `http://localhost:5000`  
  - SvelteKit frontend: `http://localhost:5173`

- **DVWA (Damn Vulnerable Web Application)**  
  GitHub: [https://github.com/digininja/DVWA](https://github.com/digininja/DVWA)  
  Optional local Docker setup or XAMPP install

### 🌐 Public Test Sites (Vulnerable on Purpose)

Use responsibly — only for educational testing:

- **Juice Shop (OWASP)**  
  URL: [https://juice-shop.herokuapp.com](https://juice-shop.herokuapp.com)  
  Docker:  
  ```bash
  docker run --rm -p 3000:3000 bkimminich/juice-shop
  ```

- **bWAPP (buggy Web App)**  
  Website: [http://www.itsecgames.com](http://www.itsecgames.com)  
  Docker:  
  ```bash
  docker pull raesene/bwapp
  ```

- **Hackazon** (Magento-like vulnerable e-commerce site)  
  Docker repo: [https://github.com/rapid7/hackazon](https://github.com/rapid7/hackazon)

- **Testfire.net** (Legacy web app used in DAST testing)  
  URL: [http://testfire.net](http://testfire.net)


## 🛠 Running Scans

* **Static Scan**: Upload `.py`, `.js`, or `.java` files.
* **Dynamic Scan**: Provide live URL and select tool (`zap` or `nikto`).

Make sure your target is accessible from the container (e.g., use `host.docker.internal` or `172.17.0.1` for local apps).

Fix folder permission errors (optional):

```bash
sudo chown -R $USER:$USER storage/app/scans
```

---

## 🗂 Folder Structure Overview

```
backend/
├── app/Http/Controllers/ScanController.php
├── resources/views/upload.blade.php
├── storage/app/scans/
├── zap-scanner/
│   ├── Dockerfile
│   └── run_zap.sh
├── nikto-scanner/
│   └── Dockerfile
```

---

## ✅ To-Do / Work in Progress

* Add progress bar / async UI
* Full results database storage
* OpenAI report generation
* Frontend integration (React or Svelte)

---

## 📄 License

MIT License © 2025 BRANACA Team

