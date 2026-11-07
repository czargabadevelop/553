# Run Migrations

Migrate the database inside the Docker `app` container.

## Basic migration

```bash
docker compose exec -T app php artisan migrate
```

## With seeders (feature flags, default settings, admin user)

```bash
docker compose exec -T app php artisan migrate --seed
```

## Force in CI / non-interactive environments

```bash
docker compose exec -T app php artisan migrate --force
```

## See what would be run (without executing)

```bash
docker compose exec -T app php artisan migrate --step
```

## Rebuild everything (danger: drops all data)

```bash
docker compose exec -T app php artisan migrate:fresh --seed --force
```

## Redis override (if you see "Class \"Redis\" not found")

The `app` container's PHP has the `redis` extension loaded, but the `Redis`
client classes are only available in the `reverb`, `queue`, and `scheduler`
services. To run migrations without Redis available locally:

```bash
cp .env .env.bak
sed -i "s/^REDIS_CLIENT=.*/REDIS_CLIENT=null/" .env
sed -i "s/^CACHE_STORE=redis/CACHE_STORE=array/" .env
sed -i "s/^QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/" .env
sed -i "s/^SESSION_DRIVER=redis/SESSION_DRIVER=array/" .env

docker compose exec -T app php artisan migrate --force

cp .env.bak .env
rm .env.bak
```
