document.addEventListener('DOMContentLoaded', () => {

    // Mobile nav toggle
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', () => menu.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
    }

    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s, transform .4s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // Photo preview
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const uploadArea = document.getElementById('uploadArea');
    if (photoInput && uploadArea) {
        uploadArea.addEventListener('click', () => photoInput.click());
        photoInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (photoPreview) {
                        photoPreview.src = e.target.result;
                        photoPreview.style.display = 'block';
                    }
                    uploadArea.querySelector('.upload-placeholder')?.remove();
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});

/* ===== WEB SERIAL API HELPERS FOR NFC (ESP32 + PN532) ===== */
const NFC = {
    port: null,
    reader: null,
    writer: null,
    logContainer: null,
    statusEl: null,
    isConnected: false,

    init(logId = 'serialLog', statusId = 'nfcStatus') {
        this.logContainer = document.getElementById(logId);
        this.statusEl = document.getElementById(statusId);
    },

    log(message, type = 'info') {
        if (!this.logContainer) return;
        const entry = document.createElement('div');
        entry.className = `log-entry log-${type}`;
        const time = new Date().toLocaleTimeString();
        entry.textContent = `[${time}] ${message}`;
        this.logContainer.appendChild(entry);
        this.logContainer.scrollTop = this.logContainer.scrollHeight;
    },

    setStatus(text, className) {
        if (!this.statusEl) return;
        this.statusEl.textContent = text;
        this.statusEl.className = `nfc-status ${className}`;
    },

    async connect() {
        if (!('serial' in navigator)) {
            this.log('Web Serial API not supported. Use Chrome/Edge.', 'error');
            this.setStatus('Browser not supported', 'error');
            return false;
        }

        try {
            this.port = await navigator.serial.requestPort();
            await this.port.open({ baudRate: 115200 });

            const textDecoder = new TextDecoderStream();
            this.port.readable.pipeTo(textDecoder.writable);
            this.reader = textDecoder.readable.getReader();

            const textEncoder = new TextEncoderStream();
            textEncoder.readable.pipeTo(this.port.writable);
            this.writer = textEncoder.writable.getWriter();

            this.isConnected = true;
            this.log('Connected to ESP32 successfully', 'success');
            this.setStatus('Connected', 'connected');
            return true;
        } catch (err) {
            this.log(`Connection failed: ${err.message}`, 'error');
            this.setStatus('Connection failed', 'error');
            return false;
        }
    },

    async disconnect() {
        try {
            if (this.reader) { this.reader.cancel(); this.reader = null; }
            if (this.writer) { this.writer.close(); this.writer = null; }
            if (this.port) { await this.port.close(); this.port = null; }
            this.isConnected = false;
            this.log('Disconnected from ESP32', 'info');
            this.setStatus('Disconnected', 'waiting');
        } catch (err) {
            this.log(`Disconnect error: ${err.message}`, 'error');
        }
    },

    async send(command) {
        if (!this.writer) {
            this.log('Not connected to device', 'error');
            return;
        }
        try {
            await this.writer.write(command + '\n');
            this.log(`Sent: ${command}`, 'info');
        } catch (err) {
            this.log(`Send error: ${err.message}`, 'error');
        }
    },

    async readLine(timeoutMs = 10000) {
        if (!this.reader) return null;
        let buffer = '';
        const deadline = Date.now() + timeoutMs;

        while (Date.now() < deadline) {
            try {
                const { value, done } = await this.reader.read();
                if (done) break;
                buffer += value;
                const lines = buffer.split('\n');
                if (lines.length > 1) {
                    return lines[0].trim();
                }
            } catch {
                break;
            }
        }
        return buffer.trim() || null;
    },

    async readUntilDone(timeoutMs = 15000) {
        const lines = [];
        const deadline = Date.now() + timeoutMs;
        let buffer = '';

        while (Date.now() < deadline) {
            try {
                const { value, done } = await this.reader.read();
                if (done) break;
                buffer += value;
                const parts = buffer.split('\n');
                buffer = parts.pop();
                for (const line of parts) {
                    const trimmed = line.trim();
                    if (trimmed) {
                        lines.push(trimmed);
                        this.log(`Received: ${trimmed}`, 'success');
                        if (trimmed === 'DONE' || trimmed === 'ERROR') {
                            return lines;
                        }
                    }
                }
            } catch {
                break;
            }
        }
        return lines;
    },

    async writeCard(touristData) {
        if (!this.isConnected) {
            this.log('Please connect to ESP32 first', 'error');
            return null;
        }

        this.log('Sending WRITE command...', 'info');
        const json = JSON.stringify(touristData);
        await this.send(`WRITE:${json}`);
        this.log('Place NFC card on reader...', 'info');

        const response = await this.readUntilDone(20000);
        return response;
    },

    async readCard() {
        if (!this.isConnected) {
            this.log('Please connect to ESP32 first', 'error');
            return null;
        }

        this.log('Sending READ command...', 'info');
        await this.send('READ');
        this.log('Place NFC card on reader...', 'info');

        const response = await this.readUntilDone(20000);
        return response;
    }
};
