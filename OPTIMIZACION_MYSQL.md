# Optimización de MySQL para Multi-Tenant

## Problema Identificado

Tu servidor tiene **30+ procesos MySQL** ejecutándose simultáneamente, cada uno usando ~1.1GB de memoria. Esto es causado por:

1. **Demasiadas conexiones abiertas** - Cada tenant puede crear múltiples conexiones
2. **Conexiones no cerradas** - Conexiones inactivas que no se cierran automáticamente
3. **Configuración de MySQL no optimizada** - `max_connections` muy alto

## Soluciones Aplicadas en Laravel

### 1. Pool de Conexiones
Se ha agregado configuración de pool en `config/database.php` para limitar conexiones simultáneas.

### 2. Timeout de Conexiones
Se ha configurado `PDO::ATTR_TIMEOUT => 60` para cerrar conexiones inactivas.

## Optimizaciones Necesarias en el Servidor

### Paso 1: Verificar Configuración Actual de MySQL

```bash
# Conectarse a MySQL
mysql -u root -p

# Ver configuración actual
SHOW VARIABLES LIKE 'max_connections';
SHOW VARIABLES LIKE 'wait_timeout';
SHOW VARIABLES LIKE 'interactive_timeout';
SHOW VARIABLES LIKE 'thread_cache_size';
SHOW STATUS LIKE 'Threads_connected';
SHOW STATUS LIKE 'Threads_running';
```

### Paso 2: Editar Configuración de MySQL

```bash
# Editar configuración de MySQL (Ubuntu/Debian)
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# O para MariaDB
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

### Paso 3: Agregar/Optimizar Configuración

Agrega o modifica estas líneas en la sección `[mysqld]`:

```ini
[mysqld]

# OPTIMIZACIÓN: Limitar conexiones máximas (ajustar según tu servidor)
# Para 8GB RAM: ~150-200 conexiones
# Para 16GB RAM: ~300-400 conexiones
# Para 24GB RAM: ~400-500 conexiones
max_connections = 200

# OPTIMIZACIÓN: Cerrar conexiones inactivas después de 60 segundos
wait_timeout = 60
interactive_timeout = 60

# OPTIMIZACIÓN: Cache de threads para reutilizar conexiones
thread_cache_size = 50

# OPTIMIZACIÓN: Tamaño de buffer para queries
query_cache_size = 0  # Desactivado en MySQL 8.0+
query_cache_type = 0

# OPTIMIZACIÓN: Buffer pool para InnoDB (70-80% de RAM disponible)
# Para 24GB RAM: ~16GB
innodb_buffer_pool_size = 16G

# OPTIMIZACIÓN: Logs de queries lentas (opcional, para debugging)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2

# OPTIMIZACIÓN: Evitar conexiones duplicadas
skip-name-resolve

# OPTIMIZACIÓN: Mejorar rendimiento de conexiones
back_log = 100
```

### Paso 4: Reiniciar MySQL

```bash
sudo systemctl restart mysql
# O para MariaDB
sudo systemctl restart mariadb
```

### Paso 5: Verificar Conexiones Activas

```bash
# Monitorear conexiones en tiempo real
watch -n 1 'mysql -u root -p -e "SHOW STATUS LIKE \"Threads_connected\"; SHOW STATUS LIKE \"Threads_running\";"'

# O desde MySQL
mysql -u root -p
SHOW PROCESSLIST;
```

## Optimizaciones Adicionales en Laravel

### 1. Configurar Variables de Entorno

Agrega a tu archivo `.env`:

```env
# Pool de conexiones
DB_POOL_MIN=2
DB_POOL_MAX=10

# Timeout de conexiones (segundos)
DB_TIMEOUT=60
```

### 2. Cerrar Conexiones Explícitamente

En comandos Artisan que procesan muchos tenants, cierra conexiones explícitamente:

```php
// Después de procesar cada tenant
DB::disconnect('tenant');
DB::disconnect('system');
```

### 3. Usar Transacciones Eficientes

```php
// En lugar de múltiples queries individuales
DB::transaction(function () {
    // Todas las operaciones aquí
});
```

## Monitoreo Continuo

### Script de Monitoreo

Crea un script `monitor_mysql.sh`:

```bash
#!/bin/bash
while true; do
    echo "=== $(date) ==="
    mysql -u root -pTU_PASSWORD -e "
        SHOW STATUS LIKE 'Threads_connected';
        SHOW STATUS LIKE 'Threads_running';
        SHOW STATUS LIKE 'Max_used_connections';
    "
    sleep 5
done
```

### Alertas

Configura alertas si las conexiones exceden un umbral:

```bash
# Ejemplo: Alerta si hay más de 150 conexiones
CONNECTIONS=$(mysql -u root -pTU_PASSWORD -e "SHOW STATUS LIKE 'Threads_connected'" | grep Threads_connected | awk '{print $2}')
if [ $CONNECTIONS -gt 150 ]; then
    echo "ALERTA: $CONNECTIONS conexiones activas"
fi
```

## Valores Recomendados por Tamaño de Servidor

### Servidor con 8GB RAM
```ini
max_connections = 150
innodb_buffer_pool_size = 4G
thread_cache_size = 30
```

### Servidor con 16GB RAM
```ini
max_connections = 300
innodb_buffer_pool_size = 10G
thread_cache_size = 50
```

### Servidor con 24GB RAM (Tu caso)
```ini
max_connections = 400
innodb_buffer_pool_size = 16G
thread_cache_size = 50
wait_timeout = 60
interactive_timeout = 60
```

## Verificación Post-Optimización

Después de aplicar los cambios, verifica:

```bash
# 1. Ver procesos MySQL
ps aux | grep mysqld | wc -l
# Debería mostrar menos procesos (idealmente 1-5 procesos principales)

# 2. Ver memoria usada por MySQL
ps aux | grep mysqld | awk '{sum+=$6} END {print sum/1024 " MB"}'

# 3. Ver conexiones activas
mysql -u root -p -e "SHOW STATUS LIKE 'Threads_connected';"
```

## Resultado Esperado

Después de estas optimizaciones:

- ✅ **Procesos MySQL**: De 30+ a 1-5 procesos principales
- ✅ **Memoria MySQL**: De ~33GB a ~2-4GB
- ✅ **Conexiones simultáneas**: Controladas y limitadas
- ✅ **Load average**: Debería bajar aún más

## Notas Importantes

1. **Ajusta `max_connections`** según tu carga real - no lo pongas muy bajo o muy alto
2. **`innodb_buffer_pool_size`** debe ser ~70% de tu RAM disponible
3. **Monitorea** durante las primeras 24-48 horas después de los cambios
4. **Haz backup** de la configuración antes de cambiar

## Comandos Útiles

```bash
# Ver todas las conexiones activas
mysql -u root -p -e "SHOW PROCESSLIST;"

# Matar conexiones inactivas (cuidado!)
mysql -u root -p -e "KILL <process_id>;"

# Ver estadísticas de conexiones
mysql -u root -p -e "SHOW STATUS LIKE 'Connections';"
mysql -u root -p -e "SHOW STATUS LIKE 'Max_used_connections';"

# Ver configuración actual
mysql -u root -p -e "SHOW VARIABLES LIKE '%connection%';"
```

