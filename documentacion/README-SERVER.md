# Flourish Documentation Local Server

## 🚀 Quick Start

### Option 1: Batch File (Windows)
```bash
# Double-click or run from command line:
start-server.bat
```

### Option 2: PowerShell
```powershell
# Right-click → Run with PowerShell:
./start-server.ps1
```

### Option 3: Manual PHP Server
```bash
# From flourishlib.com-master directory:
php -S localhost:8000
```

### Option 4: Python Server (Alternative)
```bash
# Python 3
python -m http.server 8000

# Python 2  
python -m SimpleHTTPServer 8000
```

### Option 5: Node.js Server (Alternative)
```bash
# Install http-server globally
npm install -g http-server

# Start server
http-server -p 8000
```

## 📚 Available URLs

Once server is running, access:

- **🏠 Main Site**: http://localhost:8000/
- **📖 API Documentation**: http://localhost:8000/api/
- **📑 General Docs**: http://localhost:8000/docs/
- **📰 Blog**: http://localhost:8000/blog/
- **📄 Offline Docs**: http://localhost:8000/offline_docs/flourish_v0.9.0.html

## 🎯 Key Features

- **CSS/JS Loading**: All stylesheets and scripts load correctly
- **PHP 8.4 Documentation**: Updated for modern PHP compatibility
- **Interactive Examples**: Code highlighting and syntax features
- **Complete API Reference**: All 64 classes documented
- **Migration Guide**: PHP 8.4 compatibility information

## 🛑 Stopping the Server

Press `Ctrl+C` in the terminal to stop the server.

## 🔧 Troubleshooting

### Port Already in Use
```bash
# Use different port
php -S localhost:8001
```

### PHP Not Found
```bash
# Check PHP installation
php --version

# Add PHP to PATH or use full path
C:\php\php.exe -S localhost:8000
```

### Permission Issues
- Run Command Prompt as Administrator
- Or use PowerShell as Administrator

## ✨ Features Working

✅ CSS styling loads properly  
✅ JavaScript functionality works  
✅ Code syntax highlighting active  
✅ Navigation menus functional  
✅ All documentation accessible  
✅ PHP 8.4 compatibility info visible