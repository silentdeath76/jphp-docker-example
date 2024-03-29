# jphp-docker-example

Создаем файл docker-compose.yml или качаем файл из репозитория docker-compose.yml

```dockerfile
version: '3'

services:
  postgres:
    image: postgres:14-alpine3.18
    container_name: 'jppm-app-postgres'
    ports:
      - 5432:5432
    environment:
      - 'POSTGRES_DB=jppm-app'
      - 'POSTGRES_USER=jppm-app'
      - 'POSTGRES_HOST_AUTH_METHOD=trust'
  app:
    build: https://github.com/silentdeath76/jphp-docker-example.git
    container_name: 'jppm-app'
    ports:
      - 19900:80
    environment:
      - 'POSTGRES_HOST=postgres'
      - 'POSTGRES_DB=jppm-app'
      - 'POSTGRES_USER=jppm-app'
      - 'APP_PORT=80'
    depends_on:
      - postgres
```

Переходим в команднеой строке в ту директорию в которой создали docker-compose.yml файл и выполняем команду:
```shell
docker compose up
```

Если надо запустить как сервис, то выполняем с флагом -d
```shell
docker compose up -d
```

После этого можно открыть в бразуере http://localhost:19900/