[database]
host = ${MATOMO_DATABASE_HOST}
port = ${MATOMO_DATABASE_PORT}
username = ${MATOMO_DATABASE_USERNAME}
password = ${MATOMO_DATABASE_PASSWORD}
dbname = ${MATOMO_DATABASE_DBNAME}
tables_prefix ="matomo_"
adapter = PDO\MYSQL
type = InnoDB
schema = Mysql

[General]
force_ssl=0
assume_secure_protocol = 1
proxy_client_headers[] = HTTP_X_FORWARDED_FOR
proxy_host_headers[] = HTTP_X_FORWARDED_HOST
proxy_ips[] = 192.168.*.*/16
proxy_ips[] = 172.*.*.*/8

[log]
log_writers[] = file
log_level = DEBUG

[PluginsInstalled]
PluginsInstalled[] = "EnvironmentVariables"
PluginsInstalled[] = "LogViewer"
PluginsInstalled[] = "BotTracker"
PluginsInstalled[] = "SecurityInfo"
PluginsInstalled[] = "PerformanceAudit"
