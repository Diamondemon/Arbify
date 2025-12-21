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
    for i in {1..30}; do
        if mysql -e "SELECT 1" &>/dev/null; then
            break
        fi
        sleep 1
    done
    
    # Configure root user authentication
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING PASSWORD('');"
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE:-arbify};"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-root}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE:-arbify}.* TO '${DB_USERNAME:-root}'@'localhost';"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-root}'@'%' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE:-arbify}.* TO '${DB_USERNAME:-root}'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Shutdown MySQL
    mysqladmin -u root shutdown 2>/dev/null || kill $mysql_pid
    wait $mysql_pid 2>/dev/null || true
else
    # Database exists, just ensure root user has correct authentication
    echo "Database exists, configuring authentication..."
    /usr/sbin/mysqld --user=mysql --skip-networking --socket=/var/run/mysqld/mysqld.sock &
    mysql_pid=$!
    
    # Wait for MySQL to start
    for i in {1..30}; do
        if mysql -e "SELECT 1" &>/dev/null; then
            break
        fi
        sleep 1
    done
    
    # Fix authentication for existing database
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING PASSWORD('');" 2>/dev/null || true
    mysql -e "FLUSH PRIVILEGES;"
    
    mysqladmin -u root shutdown 2>/dev/null || kill $mysql_pid
    wait $mysql_pid 2>/dev/null || true
fi

# Ensure proper permissions for Laravel
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Start all services with supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
