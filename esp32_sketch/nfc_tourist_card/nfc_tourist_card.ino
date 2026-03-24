/*
 * ESP32 + PN532 NFC RFID v3 Module — Tourist Card Reader/Writer
 *
 * Wiring (I2C mode — only 4 wires needed):
 *   PN532 SDA  -> ESP32 GPIO 21
 *   PN532 SCL  -> ESP32 GPIO 22
 *   PN532 VCC  -> 3.3V
 *   PN532 GND  -> GND
 *   PN532 IRQ  -> (not connected, uses I2C polling)
 *   PN532 RSTO -> (not connected)
 *
 *   Set PN532 DIP switches to I2C mode (Switch 1 ON, Switch 2 OFF).
 *
 * Buzzer (active):
 *   Buzzer (+) -> ESP32 GPIO 5
 *   Buzzer (-) -> GND
 *
 * Install libraries via Arduino Library Manager:
 *   - Adafruit PN532 (by Adafruit)
 *   - ArduinoJson    (by Benoit Blanchon)
 *
 * Serial protocol (115200 baud):
 *   Computer sends: READ\n          -> reads NFC card, returns JSON + DONE
 *   Computer sends: WRITE:{json}\n  -> writes JSON to NFC card, returns UID: + DONE
 */

#include <Wire.h>
#include <Adafruit_PN532.h>
#include <ArduinoJson.h>
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"

#define PN532_NO_PIN 0xFF

#define BUZZER_PIN   5

Adafruit_PN532 nfc(PN532_NO_PIN, PN532_NO_PIN);

static char cmdBuf[512];

void buzzerOn()  { digitalWrite(BUZZER_PIN, HIGH); }
void buzzerOff() { digitalWrite(BUZZER_PIN, LOW);  }

void beep(unsigned long ms) {
    buzzerOn();
    delay(ms);
    buzzerOff();
}

void beepCardDetected() {
    beep(100);
}

void beepSuccess() {
    beep(80);
    delay(80);
    beep(80);
}

void beepError() {
    beep(400);
}

bool nfcReady = false;

void initNFC() {
    Wire.begin(21, 22);
    delay(200);
    nfc.begin();
    delay(300);

    uint32_t versiondata = 0;
    for (int attempt = 0; attempt < 5; attempt++) {
        Serial.print(".");
        versiondata = nfc.getFirmwareVersion();
        if (versiondata) break;
        delay(500);
    }
    Serial.println();

    if (!versiondata) {
        Serial.println("PN532 not found. Check wiring.");
        beepError();
        nfcReady = false;
        return;
    }

    nfc.SAMConfig();
    delay(100);
    nfcReady = true;
}

void setup() {
    WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0);

    Serial.setRxBufferSize(512);
    Serial.begin(115200);
    while (!Serial) delay(10);

    pinMode(BUZZER_PIN, OUTPUT);
    buzzerOff();

    delay(1000);

    initNFC();

    if (nfcReady) {
        beep(150);
    }

    while (Serial.available()) Serial.read();
    Serial.println("READY");
}

void loop() {
    if (Serial.available()) {
        int len = Serial.readBytesUntil('\n', cmdBuf, sizeof(cmdBuf) - 1);
        cmdBuf[len] = '\0';

        while (len > 0 && (cmdBuf[len - 1] == '\r' || cmdBuf[len - 1] == ' ')) {
            cmdBuf[--len] = '\0';
        }

        if (len == 0) return;

        if (strcmp(cmdBuf, "PING") == 0) {
            Serial.println("READY");
            return;
        }

        if (!nfcReady) {
            Serial.println("NFC_REINIT");
            initNFC();
            if (!nfcReady) {
                Serial.println("ERROR");
                return;
            }
            Serial.println("NFC_OK");
        }

        if (strcmp(cmdBuf, "READ") == 0) {
            readCard();
        } else if (strncmp(cmdBuf, "WRITE:", 6) == 0) {
            writeCard(cmdBuf + 6, len - 6);
        }
    }
    delay(10);
}

void printUID(uint8_t *uid, uint8_t len) {
    Serial.print("UID:");
    for (uint8_t i = 0; i < len; i++) {
        if (uid[i] < 0x10) Serial.print('0');
        Serial.print(uid[i], HEX);
        if (i < len - 1) Serial.print(':');
    }
    Serial.println();
}

void readCard() {
    Serial.println("WAITING_FOR_CARD");

    uint8_t uid[7];
    uint8_t uidLength;

    if (!nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 10000)) {
        Serial.println("ERROR");
        beepError();
        return;
    }

    beepCardDetected();
    printUID(uid, uidLength);

    uint8_t data[16];
    bool authenticated = false;
    bool hasData = false;

    uint8_t keyA[6] = {0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF};

    for (uint8_t block = 4; block <= 62; block++) {
        if (block % 4 == 3) continue;

        if (block % 4 == 0) {
            authenticated = nfc.mifareclassic_AuthenticateBlock(uid, uidLength, block, 0, keyA);
            if (!authenticated) break;
        }

        if (authenticated && nfc.mifareclassic_ReadDataBlock(block, data)) {
            for (uint8_t j = 0; j < 16; j++) {
                if (data[j] == 0x00) goto done_reading;
                Serial.write(data[j]);
                hasData = true;
            }
        }
    }

done_reading:
    if (!hasData) {
        Serial.print("{}");
    }
    Serial.println();
    beepSuccess();
    Serial.println("DONE");
}

void writeCard(const char* jsonData, int dataLen) {
    Serial.println("WAITING_FOR_CARD");

    uint8_t uid[7];
    uint8_t uidLength;

    if (!nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 10000)) {
        Serial.println("ERROR");
        beepError();
        return;
    }

    beepCardDetected();
    printUID(uid, uidLength);

    uint8_t keyA[6] = {0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF};
    int blockIdx = 0;

    for (uint8_t block = 4; block <= 62 && blockIdx * 16 < dataLen + 1; block++) {
        if (block % 4 == 3) continue;

        if (block % 4 == 0) {
            if (!nfc.mifareclassic_AuthenticateBlock(uid, uidLength, block, 0, keyA)) {
                Serial.println("ERROR");
                beepError();
                return;
            }
        }

        uint8_t writeData[16] = {0};
        for (uint8_t j = 0; j < 16; j++) {
            int charIdx = blockIdx * 16 + j;
            if (charIdx < dataLen) {
                writeData[j] = (uint8_t)jsonData[charIdx];
            }
        }

        if (!nfc.mifareclassic_WriteDataBlock(block, writeData)) {
            Serial.println("ERROR");
            beepError();
            return;
        }
        blockIdx++;
    }

    beepSuccess();
    Serial.println("DONE");
}
