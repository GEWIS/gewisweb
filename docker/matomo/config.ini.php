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

[Plugins]
; list of plugins (in order they will be loaded) that are activated by default in the Matomo platform
Plugins[] = CorePluginsAdmin
Plugins[] = CoreAdminHome
Plugins[] = CoreHome
Plugins[] = WebsiteMeasurable
Plugins[] = IntranetMeasurable
Plugins[] = Diagnostics
Plugins[] = CoreVisualizations
Plugins[] = Proxy
Plugins[] = API
Plugins[] = Widgetize
Plugins[] = Transitions
Plugins[] = LanguagesManager
Plugins[] = Actions
Plugins[] = Dashboard
Plugins[] = MultiSites
Plugins[] = Referrers
Plugins[] = UserLanguage
Plugins[] = DevicesDetection
Plugins[] = Goals
Plugins[] = Ecommerce
Plugins[] = SEO
Plugins[] = Events
Plugins[] = UserCountry
Plugins[] = GeoIp2
Plugins[] = VisitsSummary
Plugins[] = VisitFrequency
Plugins[] = VisitTime
Plugins[] = VisitorInterest
Plugins[] = RssWidget
Plugins[] = Feedback
Plugins[] = Monolog

Plugins[] = Login
Plugins[] = TwoFactorAuth
Plugins[] = UsersManager
Plugins[] = SitesManager
Plugins[] = Installation
Plugins[] = CoreUpdater
Plugins[] = CoreConsole
Plugins[] = ScheduledReports
Plugins[] = UserCountryMap
Plugins[] = Live
Plugins[] = PrivacyManager
Plugins[] = ImageGraph
Plugins[] = Annotations
Plugins[] = MobileMessaging
Plugins[] = Overlay
Plugins[] = SegmentEditor
Plugins[] = Insights
Plugins[] = Morpheus
Plugins[] = Contents
Plugins[] = BulkTracking
Plugins[] = Resolution
Plugins[] = DevicePlugins
Plugins[] = Heartbeat
Plugins[] = Intl
Plugins[] = Marketplace
Plugins[] = ProfessionalServices
Plugins[] = UserId
Plugins[] = CustomJsTracker
Plugins[] = Tour
Plugins[] = PagePerformance
Plugins[] = CustomDimensions

Plugins[] = "LogViewer"
Plugins[] = "SecurityInfo"

[PluginsInstalled]
PluginsInstalled[] = Diagnostics
PluginsInstalled[] = Login
PluginsInstalled[] = CoreAdminHome
PluginsInstalled[] = UsersManager
PluginsInstalled[] = SitesManager
PluginsInstalled[] = Installation
PluginsInstalled[] = Monolog
PluginsInstalled[] = Intl

PluginsInstalled[] = "LogViewer"
PluginsInstalled[] = "SecurityInfo"
