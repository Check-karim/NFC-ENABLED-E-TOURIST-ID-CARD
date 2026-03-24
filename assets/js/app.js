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
    _readableClosed: null,
    _writableClosed: null,
    logContainer: null,
    statusEl: null,
    isConnected: false,
    _busy: false,

    BOOT_NOISE: /^(ets |rst:|configsip|clk_drv|mode:|load:|ho \d|entry |SPIWP)/,

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

            await this.port.setSignals({ dataTerminalReady: false, requestToSend: false });

            const decoderStream = new TextDecoderStream();
            this._readableClosed = this.port.readable.pipeTo(decoderStream.writable);
            this.reader = decoderStream.readable.getReader();

            const encoderStream = new TextEncoderStream();
            this._writableClosed = encoderStream.readable.pipeTo(this.port.writable);
            this.writer = encoderStream.writable.getWriter();

            this.isConnected = true;
            this.log('Connected — waiting for ESP32 to be ready...', 'info');
            this.setStatus('Connecting...', 'waiting');

            const ready = await this._waitForReady(8000);
            if (ready) {
                this.log('ESP32 is READY', 'success');
                this.setStatus('Connected', 'connected');
            } else {
                this.log('ESP32 connected (READY signal not received — may still work)', 'success');
                this.setStatus('Connected', 'connected');
            }
            return true;
        } catch (err) {
            this.log(`Connection failed: ${err.message}`, 'error');
            this.setStatus('Connection failed', 'error');
            return false;
        }
    },

    async _waitForReady(timeoutMs) {
        const deadline = Date.now() + timeoutMs;
        let buffer = '';
        while (Date.now() < deadline) {
            try {
                const { value, done } = await Promise.race([
                    this.reader.read(),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), deadline - Date.now()))
                ]);
                if (done) break;
                buffer += value;
                const parts = buffer.split('\n');
                buffer = parts.pop();
                for (const line of parts) {
                    const trimmed = line.trim();
                    if (!trimmed) continue;
                    if (this.BOOT_NOISE.test(trimmed)) continue;
                    if (trimmed === 'READY') return true;
                }
            } catch {
                break;
            }
        }
        return false;
    },

    async disconnect() {
        this.isConnected = false;
        try {
            if (this.reader) {
                await this.reader.cancel();
                await this._readableClosed.catch(() => {});
                this.reader = null;
                this._readableClosed = null;
            }
            if (this.writer) {
                await this.writer.close();
                await this._writableClosed.catch(() => {});
                this.writer = null;
                this._writableClosed = null;
            }
            if (this.port) {
                await this.port.close();
                this.port = null;
            }
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

    async readUntilDone(timeoutMs = 15000) {
        const lines = [];
        const deadline = Date.now() + timeoutMs;
        let buffer = '';
        let gotReboot = false;

        while (Date.now() < deadline) {
            try {
                const { value, done } = await Promise.race([
                    this.reader.read(),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), Math.max(100, deadline - Date.now())))
                ]);
                if (done) break;
                buffer += value;
                const parts = buffer.split('\n');
                buffer = parts.pop();
                for (const line of parts) {
                    const trimmed = line.trim();
                    if (!trimmed) continue;

                    if (this.BOOT_NOISE.test(trimmed)) {
                        gotReboot = true;
                        continue;
                    }

                    if (gotReboot && trimmed === 'READY') {
                        gotReboot = false;
                        this.log('ESP32 rebooted — resending command...', 'info');
                        return '__REBOOT__';
                    }

                    if (gotReboot) continue;

                    lines.push(trimmed);
                    this.log(`Received: ${trimmed}`, 'success');
                    if (trimmed === 'DONE' || trimmed === 'ERROR') {
                        return lines;
                    }
                }
            } catch {
                break;
            }
        }
        return lines;
    },

    async writeCard(touristData, _retries = 2) {
        if (!this.isConnected) {
            this.log('Please connect to ESP32 first', 'error');
            return null;
        }
        if (this._busy) {
            this.log('Device is busy, please wait...', 'error');
            return null;
        }

        this._busy = true;
        try {
            this.log('Sending WRITE command...', 'info');
            const json = JSON.stringify(touristData);
            await this.send(`WRITE:${json}`);
            this.log('Place NFC card on reader...', 'info');

            const response = await this.readUntilDone(30000);

            if (response === '__REBOOT__' && _retries > 0) {
                this.log('Retrying write after reboot...', 'info');
                await new Promise(r => setTimeout(r, 1000));
                this._busy = false;
                return this.writeCard(touristData, _retries - 1);
            }
            return response;
        } finally {
            this._busy = false;
        }
    },

    async readCard(_retries = 2) {
        if (!this.isConnected) {
            this.log('Please connect to ESP32 first', 'error');
            return null;
        }
        if (this._busy) {
            this.log('Device is busy, please wait...', 'error');
            return null;
        }

        this._busy = true;
        try {
            this.log('Sending READ command...', 'info');
            await this.send('READ');
            this.log('Place NFC card on reader...', 'info');

            const response = await this.readUntilDone(30000);

            if (response === '__REBOOT__' && _retries > 0) {
                this.log('Retrying read after reboot...', 'info');
                await new Promise(r => setTimeout(r, 1000));
                this._busy = false;
                return this.readCard(_retries - 1);
            }
            return response;
        } finally {
            this._busy = false;
        }
    }
};
