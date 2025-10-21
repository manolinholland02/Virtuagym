How to run

Prereqs: Docker & Docker Compose.

# from repo root
cd backend/docker
docker compose up --build -d

# seed local mock data (Task 1)
docker compose run --rm php-fpm composer seed

# fetch first 200 exercises (Task 2)
docker compose run --rm php-fpm composer exercises

# create ~1,000 exercise instances (Task 2)
docker compose run --rm php-fpm composer instances

Now open:
http://localhost:8080/api/exercises?offset=0&limit=10
http://localhost:8080/api/export?type=activity&format=json
http://localhost:8080/api/export?type=popular-exercises&format=csv

First time only, if `vendor/` is missing:
docker compose run --rm php-fpm composer install --no-interaction --prefer-dist