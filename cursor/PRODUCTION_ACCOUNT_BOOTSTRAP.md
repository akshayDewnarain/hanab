# Production accounts

Hanab does not ship a default login. From the server you create a hidden setup account for yourself, prepare the environment, then invite the client's first administrator. Everyone else is invited from Settings → Users.

Run the commands from the API directory. Locally, with the Hanab container running, prefix them with `docker exec hanab-laravel`.

## Before the first invite

Set these in the API environment:

- `APP_URL` — public API origin, including `https://`
- `FRONTEND_URL` — public address of the Vue app, including `https://`. Invitation and password-reset links use this value.
- `SANCTUM_STATEFUL_DOMAINS` — the frontend host, without a scheme
- `MAIL_MAILER=mailgun`
- `MAIL_FROM_ADDRESS` — an address on the verified Mailgun domain
- `MAIL_FROM_NAME`
- `MAILGUN_DOMAIN` — the sending domain, such as `mg.example.com`
- `MAILGUN_SECRET` — the Mailgun API key
- `MAILGUN_ENDPOINT=api.eu.mailgun.net` — EU region; use `api.mailgun.net` only for a US account
- `USER_INVITATION_EXPIRES_DAYS` — optional, defaults to 7

Invitation mail is sent immediately. It is not queued, so a mail failure is visible to the command and the token is not stored in the queue. A password-reset mail uses Laravel’s notification and the same mail transport.

Check the transport before onboarding:

```bash
cd api
php artisan migrate --force
php artisan tinker --execute="Mail::raw('Hanab mail check', fn (\$message) => \$message->to('you@example.com')->subject('Hanab mail check'));"
```

Production mail uses the Mailgun transport. Keep `MAIL_MAILER=log` or Mailpit locally (`MAIL_MAILER=smtp`, host `mailpit`, port `1025`). Do not enable Telescope in production; it can record request bodies.

## Your setup account

Create this first. It is your own login while you prepare production. The client does not see it in Settings → Users, search, or role counts.

```bash
php artisan users:create-internal you@example.com --name="Your Name"
```

The command requires the shell. There is no public URL that creates this account. It assigns `super-admin`, sends one invitation, and does not set a password. Open the link, choose a password of at least 12 characters, then set up an authenticator. Hanab stays closed until both are done. The token is stored only as a hash, expires after seven days, and works once.

Run the command again for the same email and it stops. It does not turn an existing client account into a setup account.

If the mail transport fails, the account still exists and no password is set. Fix mail, then:

```bash
php artisan users:create-internal you@example.com --resend
```

`--resend` rotates the token. It does not change a password that was already chosen. `--by="Your Name"` records who ran the command in the activity log.

When preparation is finished and you no longer need the login:

```bash
php artisan users:disable-internal you@example.com --by="Your Name"
```

This disables the setup account, revokes its sessions and tokens, and revokes a pending invitation. It refuses an email that is not a setup account. A disabled setup account cannot sign in.

## First client administrator

After your setup account works, invite the client's first administrator:

```bash
php artisan hanab:bootstrap-admin owner@example.com --name="First Admin"
```

The command requires the shell. There is no public URL that creates an administrator. Your setup account does not block it and does not count as the client's administrator.

It creates one invited account, assigns the existing `super-admin` role, and sends one activation email. The person opens `/invitations/accept/{token}`, chooses a password of at least 12 characters, and sets up an authenticator. The token is stored only as a hash, expires after seven days, and works once.

Run the command again and it stops. It does not create a second client administrator.

If the mail transport fails, the account still exists and no password is set. Fix mail, then:

```bash
php artisan hanab:bootstrap-admin owner@example.com --resend
```

`--resend` rotates the token. It does not change a password that was already chosen.

## When no active super-admin remains

If the initial administrator is disabled, deleted, or locked out and no other active super-admin exists:

```bash
php artisan hanab:bootstrap-admin owner@example.com --recover
```

This is refused while any active client super-admin still has a password. Your hidden setup account does not count. Recovery sends a new activation link and replaces the old password only after the person accepts it.

## After activation

Signed-in super-admins use Settings → Users to invite by email, optionally set a name, role, and colleague profile, and to resend, revoke, or disable accounts. The setup account is omitted from that list. Settings → Access control edits roles other than `super-admin`. Only a super-admin can assign the `super-admin` role. The last active client super-admin cannot be deleted, disabled, or moved off that role. A hidden setup account is not that remaining administrator.

A login and a colleague profile stay separate. Linking a colleague is optional, and one colleague can belong to only one login. Creating a colleague does not create a login.

Activated accounts reset a forgotten password from `/forgot-password`. The reply is the same whether or not the email exists. An invitation that has not been accepted does not use the reset flow.

## What a fresh install does not do

`php artisan db:seed` does not create a user. `UserSeeder` returns immediately when `APP_ENV=production`. Migrations create the `super-admin` role and the permission rows. They do not attach that role to existing accounts.

The migration `2026_11_08_193000_bootstrap_maintainer_role` used to assign `super-admin` to every user without a role. That assignment has been removed from the file. Databases that already ran the old file keep the roles they have; the file change does not run again there.
