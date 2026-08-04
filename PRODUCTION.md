# Production checklist

Use this before go-live. Local email uses `MAIL_MAILER=log` (writes to Laravel log) — all SPEC flows are implemented and queue-ready; switch to real SMTP when addresses are available.

## Security

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong unique `APP_KEY` (`php artisan key:generate`)
- [ ] `APP_URL=https://…`
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
- [ ] `LOG_LEVEL=warning` (or `error`)
- [ ] Change all seeded admin passwords (`SEED_ADMIN_PASSWORD` if re-seeding; never leave `password123`)
- [ ] HTTPS only (app forces `https` URLs when `APP_ENV=production`)
- [ ] Trust your reverse proxy / load balancer for HTTPS termination

## Auth lockout

- Defaults: 5 failed attempts → 15 minute lock (`AUTH_LOCKOUT_ATTEMPTS` / `AUTH_LOCKOUT_MINUTES`)
- Login / admin login rate-limited (`throttle:20,1`); password reset `throttle:5,1`
- System admin can manually unlock accounts

## Email (SPEC flows — ready without live SMTP)

| Flow | Mailable / mechanism | Recipient |
|------|----------------------|-----------|
| Go-live / welcome | `WelcomeStartUseMail` | Institution user |
| Password reset | Laravel `ResetPassword` (24h) | User |
| Account lockout | `AccountLockedMail` | Locked user |
| Application created | `ApplicationCreatedMail` | Admins + institution notification email |
| Special approval | `SpecialApprovalRequestedMail` | Same |
| Cancel requested | `CancelRequestedMail` | Same |
| Approved / rejected / cancel result | `Application*Mail` / `Cancel*Mail` | Requester |
| Contract plan confirmed | `ContractPlanConfirmedMail` | Institution notification email |

Ops:

1. Set `MAIL_MAILER=smtp` (or SES / failover) and from-address.
2. `QUEUE_CONNECTION=database` (default) — all app Mailables implement `ShouldQueue`.
3. Run a worker: `php artisan queue:work` (or supervisor / Railway worker).
4. Until SMTP exists, keep `MAIL_MAILER=log` and verify flows in `storage/logs/laravel.log`.

## Ops

- [ ] `php artisan storage:link`
- [ ] Cron / scheduler: `* * * * * php artisan schedule:run`
- [ ] Queue worker running
- [ ] Database backups enabled (admin UI + schedule)
- [ ] Confirm admin `receives_app_notifications` flags

## Multi-department staff

Staff may belong to multiple departments via 兼務 (`employee_positions`). Department admins see / apply for affiliated staff (primary **or** concurrent). CSV export/import supports multi-row affiliation (`職種`, `主所属` columns).
