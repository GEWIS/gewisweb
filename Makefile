development:
    @docker-compose down && \
        docker-compose \
            -f docker-compose.yml \
            -f docker-compose.development.yml \
        up -d --remove-orphans

production:
    @docker-compose down && \
        docker-compose \
            -f docker-compose.yml \
            -f docker-compose.production.yml \
        up -d --remove-orphans
