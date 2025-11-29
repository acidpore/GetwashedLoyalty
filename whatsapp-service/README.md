# WhatsApp Service - Getwashed Loyalty

Node.js service untuk mengirim notifikasi WhatsApp menggunakan **whatsapp-web.js**.

## 📋 Features

- ✅ Send WhatsApp messages via API
- ✅ Automatic phone number formatting (08xxx → 628xxx)
- ✅ Session persistence (no need to re-scan QR every time)
- ✅ Health check endpoint
- ✅ QR code display in terminal

## 🛠️ Tech Stack

- **Node.js** + Express
- **whatsapp-web.js** - WhatsApp Web API
- **qrcode-terminal** - QR code generator
- **dotenv** - Environment management

## 📦 Installation

### Prerequisites

- Node.js 16+ 
- npm
- Google Chrome/Chromium (auto-downloaded by Puppeteer)

### Setup

```bash
# Navigate to folder
cd whatsapp-service

# Install dependencies
npm install
```

## ⚙️ Configuration

Create `.env` file (optional):

```env
PORT=3000
SESSION_PATH=./.wwebjs_auth
```

## 🚀 Usage

### Start Service

```bash
npm start
# or
node server.js
```

### First Run - WhatsApp Authentication

Pada **first run**, Anda perlu scan QR code:

1. **QR code akan muncul di terminal**
   ```
   ========================================
   QR CODE RECEIVED - Scan with WhatsApp
   ========================================
   
   █████████████████████████
   █ ▄▄▄▄▄ █▀ ▄ █ ▄▄▄▄▄ █
   ...
   ```

2. **Buka WhatsApp di HP**
   - Tap **⚙️ Settings**
   - Pilih **Linked Devices** / **Perangkat Tertaut**
   - Tap **Link a Device**

3. **Scan QR code** yang muncul di terminal

4. **Tunggu konfirmasi**
   ```
   ✅ WhatsApp authenticated successfully
   ✅ WhatsApp client is ready!
   Service running on port: 3000
   ```

### Subsequent Runs

Session tersimpan di folder `.wwebjs_auth`, jadi **tidak perlu scan ulang**:

```bash
npm start

# Output:
# 🚀 WhatsApp service started on port 3000
# ✅ WhatsApp authenticated successfully
# ✅ WhatsApp client is ready!
```

## 📡 API Endpoints

### `GET /status`

Check service health.

**Response:**
```json
{
  "success": true,
  "ready": true,
  "message": "WhatsApp service is ready"
}
```

### `POST /send-message`

Send WhatsApp message.

**Request:**
```json
{
  "phone": "628123456789",
  "message": "Hello from Getwashed!"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "phone": "628123456789"
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "WhatsApp client is not ready"
}
```

## 🧪 Testing

### Via cURL

```bash
# Windows PowerShell
curl -X POST http://localhost:3000/send-message `
  -H "Content-Type: application/json" `
  -d '{\"phone\":\"628123456789\",\"message\":\"Test message\"}'
```

### Via Postman

- **Method**: POST
- **URL**: `http://localhost:3000/send-message`
- **Headers**: `Content-Type: application/json`
- **Body**:
  ```json
  {
    "phone": "628123456789",
    "message": "Test pesan dari API"
  }
  ```

### Via Browser

Check status:
```
http://localhost:3000/status
```

## 📱 Phone Number Format

Service otomatis format nomor telepon:

| Input | Output |
|-------|--------|
| `08123456789` | `628123456789` |
| `8123456789` | `628123456789` |
| `628123456789` | `628123456789` |
| `+628123456789` | `628123456789` |

## 📊 Logging

Logs tampil langsung di terminal:

```bash
# Service start
🚀 WhatsApp service started on port 3000
Waiting for WhatsApp client initialization...

# Authentication
✅ WhatsApp authenticated successfully
✅ WhatsApp client is ready!

# Message sent
✅ Message sent to 628123456789

# Errors
❌ Error sending message: Phone number not registered
⚠️ WhatsApp disconnected: Session logged out
```

## 🐛 Troubleshooting

### Problem: QR Code tidak muncul

**Solution:**
1. Pastikan Chrome/Chromium terinstall
2. Hapus folder `.wwebjs_auth` dan `.wwebjs_cache`
3. Restart service

### Problem: Port Already in Use

```bash
# Windows
taskkill /F /IM node.exe

# Atau cari process yang pakai port 3000
netstat -ano | findstr :3000
taskkill /F /PID <PID_NUMBER>
```

### Problem: WhatsApp Disconnected

**Penyebab:**
- Koneksi internet terputus
- Logout dari WhatsApp
- Session expired

**Solution:**
1. Check internet connection
2. Logout dari semua WhatsApp Web/Desktop
3. Hapus folder `.wwebjs_auth` 
4. Restart service dan scan QR ulang

### Problem: Message Not Sent

**Check:**
- [ ] Service status: `curl http://localhost:3000/status`
- [ ] WhatsApp client ready? (`ready: true`)
- [ ] Nomor HP terdaftar di WhatsApp?
- [ ] Format nomor benar? (628xxx)

## 📁 File Structure

```
whatsapp-service/
├── .wwebjs_auth/        # Session storage (auto-created)
├── .wwebjs_cache/       # Puppeteer cache (auto-created)
├── node_modules/        # Dependencies
├── .env                 # Configuration (optional)
├── .gitignore          # Git ignore rules
├── package.json        # NPM config
├── README.md           # This file
├── server.js           # Main server
└── start-whatsapp.bat  # Windows shortcut
```

## 🔒 Security Notes

- `.wwebjs_auth` contains sensitive session data - **DO NOT commit to git**
- Use environment variables for sensitive config
- Consider using PM2 or similar for production
- Setup firewall rules for production deployment

## 🚀 Production Deployment

### Using PM2 (Recommended)

```bash
# Install PM2 globally
npm install -g pm2

# Start service with PM2
pm2 start server.js --name whatsapp-service

# Auto-restart on server reboot
pm2 startup
pm2 save

# Monitor
pm2 logs whatsapp-service
pm2 status
```

### As Windows Service

Use tools like:
- **node-windows**
- **NSSM (Non-Sucking Service Manager)**

## ⚙️ Integration with Laravel

Di file `.env` Laravel:

```env
WHATSAPP_PROVIDER=local
WHATSAPP_LOCAL_URL=http://localhost:3000

# For mobile access
WHATSAPP_LOCAL_URL=http://192.168.x.x:3000
```

Laravel akan otomatis call endpoint `/send-message` saat:
- Customer check-in (kirim OTP)
- Customer request OTP untuk login

## 📝 Notes

- Session valid selama WhatsApp tidak logout
- Chromium (~200MB) auto-download saat first install
- Service bisa handle multiple concurrent requests
- Rate limiting handled by WhatsApp (not this service)

## 📄 License

Part of Getwashed Loyalty System - Private & Proprietary

---

**Need help?** Check main project [README](../README.md) or documentation.
