up:      ## build & up
\tdocker compose up -d --build
down:    ## stop
\tdocker compose down
logs:
\tdocker compose logs -f --tail=200
sh:
\tdocker compose exec yii2-app bash
mysql:
\tdocker compose exec yii2-db mysql -uroot -p$$DB_PASSWORD
import:  ## make import DB=db8 FILE=storage/databases/db8.sql
\tdocker compose exec yii2-db sh -lc 'mysql -uroot -p$$DB_PASSWORD -e "DROP DATABASE IF EXISTS $(DB); CREATE DATABASE $(DB) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'
\tdocker compose exec yii2-app sh -lc 'mysql -h$$DB_HOST -P$$DB_PORT -u$$DB_USER -p$$DB_PASSWORD $(DB) < $(FILE)'
