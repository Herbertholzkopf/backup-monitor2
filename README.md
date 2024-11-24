# backup-monitor2



# Installationsschritte:

Installations-Skript von GitHub herunterladen:
```
wget https://raw.githubusercontent.com/Herbertholzkopf/backup-monitor2/refs/heads/main/install/install.sh
```

Skript ausführbar machen:
```
chmod +x install.sh
```

Skript ausführen:
```
./install.sh
```

Bei der Einrichtung wird das Kennwort für die Datenbank festgelegt (root Bentzer und backup_user).
Das Passwort für den backup_user muss unter /var/www/backup-monitor2/config in die database.py eingetragen werden.
```
nano /var/www/backup-monitor2/config/database.py
```



#################################
# ausführen:
/var/www/backup-monitor2/mail-to-database.py



# Test-Datenbank für externer Erreichbarkeit aktivieren (nur für die Entwicklung!)

```
sudo mysql -u root -p
```

```
CREATE USER 'newuser'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'newuser'@'%';
FLUSH PRIVILEGES;
exit;
```

```
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

In der Zeile mit bind-address das # entfernen und die Zeile ändern auf: bind-address = 0.0.0.0

```
sudo systemctl restart mysql
```
