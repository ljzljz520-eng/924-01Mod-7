#!/bin/bash
set -e

DATADIR=/var/lib/mysql
SOCKET=/run/mysqld/mysqld.sock

if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
  echo "MYSQL_ROOT_PASSWORD must be set"
  exit 1
fi

# Initialize database if missing
if [ ! -d "$DATADIR/mysql" ]; then
  echo "==> Initializing MySQL data directory..."
  mkdir -p "$DATADIR"
  chown -R mysql:mysql "$DATADIR"
  mysqld --initialize-insecure --user=mysql --datadir="$DATADIR"

  echo "==> Starting temporary MySQL server for setup..."
  mysqld --skip-networking --socket="$SOCKET" --user=mysql --datadir="$DATADIR" &
  pid="$!"

  for i in {30..0}; do
    if mysqladmin --socket="$SOCKET" ping &>/dev/null; then
      break
    fi
    echo "Waiting for MySQL to start ($i)..."
    sleep 1
  done

  if ! mysqladmin --socket="$SOCKET" ping &>/dev/null; then
    echo "ERROR: MySQL failed to start"
    exit 1
  fi

  echo "==> Applying root password and creating database/user..."
  echo "==> Creating database: ${MYSQL_DATABASE}"
  echo "==> Creating user: ${MYSQL_USER}@%"
  mysql --protocol=socket --socket="$SOCKET" -uroot <<EOSQL
    ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';
    CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
    GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL
  echo "==> Database and user created successfully"

  if [ -f /docker-entrypoint-initdb.d/init.sql ]; then
    echo "==> Loading seed data from init.sql..."
    mysql --protocol=socket --socket="$SOCKET" -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /docker-entrypoint-initdb.d/init.sql
    echo "==> Seed data loaded successfully"
  fi

  echo "==> Shutting down temporary server..."
  mysqladmin --protocol=socket --socket="$SOCKET" -uroot -p"$MYSQL_ROOT_PASSWORD" shutdown
  wait "$pid"
  echo "==> Initialization complete"
else
  echo "==> MySQL data directory already exists, skipping initialization"
fi

echo "MySQL ready. Starting server..."
exec mysqld --user=mysql --datadir="$DATADIR" --bind-address=0.0.0.0
