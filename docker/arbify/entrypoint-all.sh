#!/usr/bin/env bash
set -e

# Ensure correct ownership of MySQL directory
chown -R mysql:mysql /var/lib/mysql

# Initialize MySQL data directory if it doesn't exist
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MySQL database..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
    
    # Start MySQL temporarily
    /usr/sbin/mysqld --user=mysql --skip-networking --socket=/var/run/mysqld/mysqld.sock &
    mysql_pid=$!
    
    # Wait for MySQL to start
    sleep 5
    
    # Configure root user authentication
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING '';"
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE:-arbify};"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-root}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE:-arbify}.* TO '${DB_USERNAME:-root}'@'localhost';"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-root}'@'%' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE:-arbify}.* TO '${DB_USERNAME:-root}'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Shutdown MySQL
    mysqladmin -u root shutdown
    wait $mysql_pid
fi

# Ensure proper permissions for Laravel
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Start all services with supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
