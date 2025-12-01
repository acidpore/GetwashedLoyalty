require('dotenv').config();
const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 3000;

const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: process.env.SESSION_PATH || './.wwebjs_auth'
    }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

let isReady = false;

client.on('qr', (qr) => {
    console.log('\n========================================');
    console.log('QR CODE RECEIVED - Scan with WhatsApp');
    console.log('========================================\n');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    isReady = true;
    console.log('\n✅ WhatsApp client is ready!');
    console.log('Service running on port:', PORT);
});

client.on('authenticated', () => {
    console.log('✅ WhatsApp authenticated successfully');
});

client.on('auth_failure', (msg) => {
    console.error('❌ Authentication failure:', msg);
    isReady = false;
});

client.on('disconnected', (reason) => {
    console.log('⚠️ WhatsApp disconnected:', reason);
    isReady = false;
});

client.initialize();

app.get('/status', (req, res) => {
    res.json({
        success: true,
        ready: isReady,
        message: isReady ? 'WhatsApp service is ready' : 'WhatsApp service is not ready'
    });
});

app.post('/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                message: 'Phone and message are required'
            });
        }

        if (!isReady) {
            return res.status(503).json({
                success: false,
                message: 'WhatsApp client is not ready. Please wait or scan QR code.'
            });
        }

        let formattedPhone = phone.replace(/[^0-9]/g, '');

        if (formattedPhone.startsWith('0')) {
            formattedPhone = '62' + formattedPhone.substring(1);
        } else if (!formattedPhone.startsWith('62')) {
            formattedPhone = '62' + formattedPhone;
        }

        const chatId = `${formattedPhone}@c.us`;

        await client.sendMessage(chatId, message);

        console.log(`✅ Message sent to ${formattedPhone}`);

        res.json({
            success: true,
            message: 'Message sent successfully',
            phone: formattedPhone
        });

    } catch (error) {
        console.error('❌ Error sending message:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to send message',
            error: error.message
        });
    }
});

app.post('/send-batch', async (req, res) => {
    try {
        const { messages } = req.body;

        if (!messages || !Array.isArray(messages) || messages.length === 0) {
            return res.status(400).json({
                success: false,
                message: 'Messages array is required and cannot be empty'
            });
        }

        if (!isReady) {
            return res.status(503).json({
                success: false,
                message: 'WhatsApp client is not ready. Please wait or scan QR code.'
            });
        }

        // Respond immediately to prevent timeout
        res.json({
            success: true,
            message: `Batch processing started for ${messages.length} messages`,
            count: messages.length
        });

        // Process messages in background
        processBatch(messages);

    } catch (error) {
        console.error('❌ Error starting batch:', error);
        if (!res.headersSent) {
            res.status(500).json({
                success: false,
                message: 'Failed to start batch processing',
                error: error.message
            });
        }
    }
});

async function processBatch(messages) {
    console.log(`\n📦 Starting batch of ${messages.length} messages...`);

    for (const [index, item] of messages.entries()) {
        const { phone, message } = item;

        try {
            let formattedPhone = phone.replace(/[^0-9]/g, '');

            if (formattedPhone.startsWith('0')) {
                formattedPhone = '62' + formattedPhone.substring(1);
            } else if (!formattedPhone.startsWith('62')) {
                formattedPhone = '62' + formattedPhone;
            }

            const chatId = `${formattedPhone}@c.us`;

            // Add random delay between 2-5 seconds to prevent blocking
            const delay = Math.floor(Math.random() * 3000) + 2000;
            if (index > 0) await new Promise(resolve => setTimeout(resolve, delay));

            await client.sendMessage(chatId, message);
            console.log(`✅ [${index + 1}/${messages.length}] Sent to ${formattedPhone}`);

        } catch (error) {
            console.error(`❌ [${index + 1}/${messages.length}] Failed to send to ${phone}:`, error.message);
        }
    }

    console.log('🏁 Batch processing completed\n');
}

app.listen(PORT, () => {
    console.log(`🚀 WhatsApp service started on port ${PORT}`);
    console.log('Waiting for WhatsApp client initialization...');
});
