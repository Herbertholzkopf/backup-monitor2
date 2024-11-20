#!/bin/bash
# install.sh

# Farben für Ausgaben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "Backup-Monitor Installations Skript"
echo "================================"

# Root-Rechte prüfen
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Bitte als root ausführen${NC}"
    exit 1
fi

# Systemaktualisierung
echo -e "${YELLOW}Systemkomponenten werden aktualisiert...${NC}"
apt-get update
apt-get upgrade -y

# Installation von Python & Python-Paketen
echo -e "${YELLOW}Python wird installiert...${NC}"
apt-get install -y python3 python3-pymysql

# Installation von weiteren Paketen
echo -e "${YELLOW}MySQL, git, unzip, Nginx wird installiert...${NC}"
apt-get install -y mysql-server git unzip nginx




# Konfiguration von MySQL
echo -e "${YELLOW}MySQL Root Passwort setzen...${NC}"
read -s -p "Gewünschtes MySQL Root Passwort: " mysqlpass
echo ""

mysql --user=root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH auth_socket;
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

sudo mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${mysqlpass}';
FLUSH PRIVILEGES;
EOF

if ! mysql --user=root --password="${mysqlpass}" -e "SELECT 1;" >/dev/null 2>&1; then
    echo -e "${RED}Fehler beim Setzen des MySQL Root-Passworts.${NC}"
    exit 1
fi

echo -e "${GREEN}MySQL Root-Passwort erfolgreich gesetzt.${NC}"




# Einrichtung der MySQL Datenbank
echo -e "${YELLOW}Erstelle Datenbank "backup_monitor2" und Benutzer "backup_user"...${NC}"
read -s -p "Backup-Monitor Datenbank-Benutzer Passwort: " dbpass
echo ""

if ! mysql --user=root --password="${mysqlpass}" <<EOF
CREATE DATABASE IF NOT EXISTS backup_monitor2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'backup_user'@'localhost' IDENTIFIED BY '${dbpass}';
GRANT ALL PRIVILEGES ON backup_monitor2.* TO 'backup_user'@'localhost';
FLUSH PRIVILEGES;
EOF
then
    echo -e "${RED}Fehler beim Erstellen der Datenbank und des Benutzers.${NC}"
    exit 1
fi

echo -e "${GREEN}Datenbank und Benutzer erfolgreich erstellt.${NC}"



# Verzeichnis für das Projekt erstellen
echo -e "${YELLOW}Erstelle Projekt-Verzeichnis...${NC}"
mkdir -p /var/www/backup-monitor2

# Projekt von GitHub klonen
echo -e "${YELLOW}Klone Git Repository...${NC}"
if git clone https://github.com/Herbertholzkopf/backup-monitor2.git /var/www/backup-monitor2; then
    echo -e "${GREEN}Repository erfolgreich geklont${NC}"
else
    echo -e "${RED}Fehler beim Klonen des Repositories${NC}"
    exit 1
fi

# Rechte des Verzeichnis anpassen
chown -R www-data:www-data /var/www/backup-monitor/public/install

# Nginx Konfiguration erstellen
echo -e "${YELLOW}Konfiguriere Nginx...${NC}"
cat > /etc/nginx/sites-available/backup-monitor2 <<EOF
server {
    listen 80;
    server_name _;
    root /var/www/backup-monitor2;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

}
EOF

# Nginx Site verknüpfen und aktivieren
ln -s /etc/nginx/sites-available/backup-monitor2 /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx

# Dienste neu starten
echo -e "${YELLOW}Dienste werden neu gestartet...${NC}"
systemctl restart nginx

## Cron-Jobs einrichten
#echo -e "${YELLOW}Richte Cron-Jobs ein...${NC}"
#cat > /etc/cron.d/backup-monitor <<EOF
## Backup-Monitor Cron Jobs
#*/5 * * * * www-data php /var/www/backup-monitor/cron/fetch_mails.php
#2-59/5 * * * * www-data php /var/www/backup-monitor/cron/analyze_mails.php
#EOF


echo -e "${GREEN}Installation abgeschlossen!${NC}"
echo -e "${YELLOW}Mit der Einrichtung kann nun im Browser fortgefahren werden: http://ihre-domain.de/ bzw. http://ihre-domain.de/settings"


# Backup Datenbank, Benutzer und Kennwort müssen noch in die /config/database.py eingetragen werden
# Mail-Zugangsdaten müssen noch unter /config/mail.py eingetragen werden