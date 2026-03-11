/*
 * ESP32 + PN532 NFC RFID v3 Module — Tourist Card Reader/Writer
 *
 * Wiring (SPI mode):
 *   PN532 SCK  -> ESP32 GPIO 18
 *   PN532 MISO -> ESP32 GPIO 19
 *   PN532 MOSI -> ESP32 GPIO 23
 *   PN532 SS   -> ESP32 GPIO  5
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
#include <SPI.h>
#include <Adafruit_PN532.h>
#include <ArduinoJson.h>

#define PN532_SCK  18
#define PN532_MISO 19
#define PN532_MOSI 23
#define PN532_SS    5

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);

void setup() {
    Serial.begin(115200);
    while (!Serial) delay(10);

    nfc.begin();
    uint32_t versiondata = nfc.getFirmwareVersion();
    if (!versiondata) {
        Serial.println("ERROR");
        Serial.println("PN532 not found. Check wiring.");
        while (1) delay(1000);
    }

    nfc.SAMConfig();
    Serial.println("READY");
}

void loop() {
    if (Serial.available()) {
        String cmd = Serial.readStringUntil('\n');
        cmd.trim();

        if (cmd == "READ") {
            readCard();
        } else if (cmd.startsWith("WRITE:")) {
            String jsonData = cmd.substring(6);
            writeCard(jsonData);
        }
    }
}

void readCard() {
    Serial.println("WAITING_FOR_CARD");

    uint8_t uid[7];
    uint8_t uidLength;

    if (!nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 10000)) {
        Serial.println("ERROR");
        return;
    }

    String uidStr = "";
    for (uint8_t i = 0; i < uidLength; i++) {
        if (uid[i] < 0x10) uidStr += "0";
        uidStr += String(uid[i], HEX);
        if (i < uidLength - 1) uidStr += ":";
    }
    uidStr.toUpperCase();
    Serial.println("UID:" + uidStr);

    String cardData = "";
    uint8_t data[16];
    bool authenticated = false;

    /* Read blocks 4-15 (sector 1-3) where tourist data is stored.
       Default key A for MIFARE Classic. */
    uint8_t keyA[6] = {0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF};

    for (uint8_t block = 4; block <= 62; block++) {
        if (block % 4 == 3) continue; // skip trailer blocks

        if (block % 4 == 0) {
            authenticated = nfc.mifareclassic_AuthenticateBlock(uid, uidLength, block, 0, keyA);
            if (!authenticated) break;
        }

        if (authenticated && nfc.mifareclassic_ReadDataBlock(block, data)) {
            for (uint8_t j = 0; j < 16; j++) {
                if (data[j] == 0x00) goto done_reading;
                cardData += (char)data[j];
            }
        }
    }

done_reading:
    if (cardData.length() > 0) {
        Serial.println(cardData);
    } else {
        Serial.println("{}");
    }
    Serial.println("DONE");
}

void writeCard(String jsonData) {
    Serial.println("WAITING_FOR_CARD");

    uint8_t uid[7];
    uint8_t uidLength;

    if (!nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 10000)) {
        Serial.println("ERROR");
        return;
    }

    String uidStr = "";
    for (uint8_t i = 0; i < uidLength; i++) {
        if (uid[i] < 0x10) uidStr += "0";
        uidStr += String(uid[i], HEX);
        if (i < uidLength - 1) uidStr += ":";
    }
    uidStr.toUpperCase();
    Serial.println("UID:" + uidStr);

    uint8_t keyA[6] = {0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF};

    int dataLen = jsonData.length();
    int blockIdx = 0;

    for (uint8_t block = 4; block <= 62 && blockIdx * 16 < dataLen + 1; block++) {
        if (block % 4 == 3) continue;

        if (block % 4 == 0) {
            if (!nfc.mifareclassic_AuthenticateBlock(uid, uidLength, block, 0, keyA)) {
                Serial.println("ERROR");
                Serial.println("Authentication failed at block " + String(block));
                return;
            }
        }

        uint8_t writeData[16] = {0};
        for (uint8_t j = 0; j < 16; j++) {
            int charIdx = blockIdx * 16 + j;
            if (charIdx < dataLen) {
                writeData[j] = jsonData[charIdx];
            }
        }

        if (!nfc.mifareclassic_WriteDataBlock(block, writeData)) {
            Serial.println("ERROR");
            Serial.println("Write failed at block " + String(block));
            return;
        }
        blockIdx++;
    }

    Serial.println("DONE");
}
