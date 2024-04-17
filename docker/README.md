## Use Docker Cli
### build form source

your can change `iyuu-plus-dev:v0.0.0` to any name you want.

``` sh
# in this dir
docker build -f Dockerfile -t iyuu-plus-dev:v0.0.0 ..
# in root of project
docker build -f docker/Dockerfile -t iyuu-plus-dev:v0.0.0 .
```

### run

> if you changed `iyuu-plus-dev:v0.0.0` in last step. replace it with the same name

change `${CHANGE_IT_1/2}` to your own password. and use the {MARIADB_PASSWORD} in iyuu install step, the {MARIADB_ROOT_PASSWORD} used to backup/restore datebase

``` sh
MARIADB_ROOT_PASSWORD=${CHANGE_IT_1}
MARIADB_PASSWORD=${CHANGE_IT_2}
mkdir -p runtime/{logs,views} db-data
docker run -d --name iyuu-db -v `pwd`/db-data:/var/usr/mysql -e MARIADB_ROOT_PASSWORD=${CHANGE_IT} -e MARIADB_USER=iyuu -e MARIADB_PASSWORD=${CHANGE_IT} -e MARIADB_DATABASE=iyuu_dev -p 3306:3306 mariadb:11.3.2
docker run -d --name iyuu-plus-dev -v `pwd`/runtime/:/iyuu/runtime --link iyuu-db -p 8787:8787 iyuu-plus-dev:v0.0.0 
```

## Use docker-compose

```sh
# must change password in docker-compose.yaml before start!!!
docker-compose up -d
```
