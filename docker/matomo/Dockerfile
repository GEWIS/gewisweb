FROM matomo:5.10-apache AS gewisweb_matomo

RUN apt-get update && apt-get install -y --no-install-recommends \
        tzdata \
        gettext-base \
        unzip \
    && ln -fs /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo 'Europe/Amsterdam' > /etc/timezone \
    && dpkg-reconfigure -f noninteractive tzdata \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl -o LogViewer.zip \
      https://plugins.matomo.org/api/2.0/plugins/LogViewer/download/latest \
      && unzip LogViewer.zip \
      && rm LogViewer.zip \
      && mv LogViewer /usr/src/matomo/plugins

RUN curl -o SecurityInfo.zip \
      https://plugins.matomo.org/api/2.0/plugins/SecurityInfo/download/latest \
      && unzip SecurityInfo.zip \
      && rm SecurityInfo.zip \
      && mv SecurityInfo /usr/src/matomo/plugins

RUN curl -L -o AnalyticsOptOut.zip \
      https://github.com/GEWIS/gewisweb-analytics-opt-out/archive/refs/tags/v1.0.3-gewisweb.zip \
      && unzip -j AnalyticsOptOut.zip -d "AnalyticsOptOut" \
      && rm AnalyticsOptOut.zip \
      && mv AnalyticsOptOut /usr/src/matomo/plugins
