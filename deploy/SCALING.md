# VenueCloud Scaling & Operations Runbook

This runbook is an incremental scaling plan for the Laravel 12 / Livewire 4
application in this repository. It favors measured changes over adding services
before there is evidence they are needed. Review it together with
[`DEPLOYMENT.md`](DEPLOYMENT.md).

## What the application already supports

- MySQL and PostgreSQL connections are available in Laravel's database config;
  SQLite is intended for development and tests.
- Redis cache, queue, and session drivers are available. Production deployment
  guidance already calls for Redis, and the Supervisor example runs Redis queue
  workers.
- Booking creation uses a cache-backed distributed lock plus a database
  transaction and checks for an existing active reservation. Use a shared Redis
  cache/lock store on multi-instance deployments; per-instance file or array
  caches cannot coordinate booking locks.
- Public venue results use pagination. The marketplace now applies the same
  active/approved tenant visibility rules, eager-loads its card relations, and
  paginates the catalog instead of materializing every hall.
- A `/up` health endpoint, Nginx configuration, a deployment script, and a
  Supervisor worker example are present.

These are application capabilities, not proof of production capacity. No load
target, production traffic profile, managed Redis/database topology, object
storage, or APM integration is provisioned by this repository.

## Production baseline (single application node)

1. Use a managed MySQL/PostgreSQL instance and a shared, authenticated Redis
   service. Keep credentials and network access private; require TLS where the
   service/network requires it.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, and a unique `APP_KEY`. For
   multiple web nodes, set `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, and
   `QUEUE_CONNECTION=redis`. Ensure `REDIS_*` points to the shared service and
   that the PHP Redis extension is installed. Keep the local `.env` out of Git.
3. Keep PHP-FPM and Nginx stateless. Do not use local file sessions or a
   per-node cache for booking locks. `FILESYSTEM_DISK=s3` is supported by
   `config/filesystems.php`; configure the AWS/S3-compatible credentials and
   bucket before selecting it. Local uploaded media is not shared across web
   nodes.
4. Run queue workers under Supervisor. Start with the checked-in process count
   and adjust only after observing queue wait time, worker memory, CPU, and job
   duration. Web PHP-FPM capacity and queue worker capacity are separate pools.
5. Run `php artisan config:cache`, `php artisan route:cache`,
   `php artisan view:cache`, and `php artisan event:cache` during deployment as
   described in `deploy.sh`. Restart workers after each release.

## Scale-out readiness checklist

Before adding application nodes:

- Put nodes behind a health-checked load balancer; use `/up` for liveness, and
  a separate dependency-aware readiness check if infrastructure requires one.
- Confirm sessions, cache locks, and queues use shared Redis (or another
  deliberately selected shared backend).
- Move user uploads to S3-compatible storage and serve them through a CDN. Verify
  upload URLs, access controls, lifecycle policies, and backups before cutover.
- Store logs centrally and monitor HTTP 5xx rate/latency, database connection
  saturation and slow queries, Redis memory/latency, queue depth/wait time,
  failed jobs, disk, and PHP-FPM saturation. Alert on sustained regressions,
  not isolated spikes.
- Define backup retention and recovery objectives. Perform a restore drill to a
  separate database; a successful dump alone does not prove recoverability.
- Use rolling/blue-green releases only after database migrations are compatible
  with both old and new application versions. Back up before destructive schema
  work and do not automatically roll back irreversible migrations.

## Database and query workflow

1. Capture representative slow-query logs and endpoint traces under realistic
   traffic before changing indexes or schema.
2. Run `EXPLAIN` / `EXPLAIN ANALYZE` for the measured query on the production DB
   engine and representative data volume. Check tenant filtering, sort order,
   row estimates, and lock waits.
3. Add a migration for a justified index; estimate its build/lock impact on a
   production-sized copy, deploy during a safe window, and compare query plans
   and latency after release. Avoid speculative indexes: they cost disk and
   slow writes.
4. Keep tenant IDs in every tenant-owned data query. Re-check tenant isolation
   whenever changing query scopes, caching, jobs, exports, or object-storage
   paths. Cache keys for tenant data must include the tenant identifier.
5. Partitioning, read replicas, and connection pooling are later-stage options:
   introduce them only when metrics identify a specific bottleneck and the
   application's consistency requirements have been reviewed.

## Queue and booking operations

- Check queue depth and oldest-job age; inspect `failed_jobs` and retry only
  after identifying whether a job is safe to run again.
- The current booking flow creates its reservation, payment, and kitchen order
  inside a database transaction and dispatches a domain event. Moving side
  effects to async jobs should use after-commit dispatch and idempotent handlers;
  never queue the atomic slot check itself or tell the guest a booking succeeded
  before its transaction commits.
- The presence of a Redis queue driver does not mean all current work is already
  asynchronous. Before adding jobs, inspect event listeners and synchronous
  request work, then choose operations that can safely happen after commit
  (for example, notifications) and test retry/duplicate behavior.
- Booking-lock availability depends on the configured cache driver supporting
  atomic locks. Verify the shared Redis connection in staging with concurrent
  requests before scaling booking traffic across nodes.

## Release and load-test gates

Before a public launch or major scale increase:

```bash
php artisan test
npm ci && npm run build
php artisan migrate:status
php artisan about
curl -fsS https://YOUR_HOST/up
```

Run a staging load test with synthetic tenants and bookings; define target
concurrent users, request mix, p95/p99 latency, error rate, and booking
contention beforehand. Confirm no duplicate active hall/date/slot reservations,
no cross-tenant data exposure, stable DB/Redis connection counts, bounded queue
lag, and a successful restore. Record the baseline and repeat after infrastructure
or query changes.

## Growth phases

| Phase | Trigger | Practical next action |
|---|---|---|
| Optimize | Slow endpoints or rising database time | Profile queries, remove N+1/unbounded result sets, add measured indexes |
| Separate workloads | Web latency affected by background work | Add idempotent after-commit jobs; scale queue workers independently |
| Add web nodes | PHP-FPM saturation with spare DB/Redis capacity | Shared Redis sessions/cache/locks, shared object storage, load balancer |
| Add database capacity | DB CPU, I/O, or connection saturation persists after query tuning | Right-size managed DB, pooling, then carefully selected read replicas |
| Large data growth | Measured retention/maintenance issue on high-volume tables | Archive by business retention policy; evaluate partitioning with engine-specific tests |

Do not use horizontal scaling to hide inefficient queries or unbounded result
sets; that usually transfers the bottleneck to the database or cache.