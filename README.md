# backup-monitor2



# Installationsschritte:

apt update
apt install python3 python3-pymysql -y


cd 
mkdir -p /var/www/backup-monitor2/config
cd /var/www/backup-monitor2

nano mail-to-database.py

chmod +x /var/www/backup-monitor2/mail-to-database.py

cd config
nano database.py

nano mail.py





#################################
# Ausführbar machen:
chmod +x /var/www/backup-monitor2/mail-to-database.py


#################################
# ausführen:
/var/www/backup-monitor2/mail-to-database.py



# Test-Datenbank mit externer Erreichbarkeit (für die Entwicklung) erstellen

sudo apt update
sudo apt install mysql-server

sudo mysql_secure_installation
--> externen root-Zugriff erlauben!

sudo mysql

ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'new_password';
FLUSH PRIVILEGES;

CREATE USER 'newuser'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'newuser'@'%';
FLUSH PRIVILEGES;
exit;



sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

Finden Sie die Zeile mit bind-address und kommentieren Sie sie aus oder ändern Sie sie zu bind-address = 0.0.0.0

sudo systemctl restart mysql