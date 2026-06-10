# AGENTS.md

Guidance for AI coding agents working in this repository. Humans should read `README.md` first.

## What this project is

GEWISWEB is the GEWIS member-facing website. It serves activities, decisions, photos, education materials, custom pages, and a careers portal for partner companies. The codebase was recently migrated from Laminas MVC to Symfony 8; some structure is still settling — `tests/` is empty save for bootstrap files, `src/ApiResource/` is empty, and the `src/Service/{Decision,Education,Photo}/` directories are still `.gitignore`-only placeholders. Treat the present-day layout as authoritative, but expect to see Laminas-shaped patterns lurking in older code paths.

## Pre-migration reference

`gewisweb-laminas/` is a git submodule pointing at the previous Laminas MVC codebase, pinned at the last pre-migration commit (`df1bc5b8c`, shared with this repo's history). It exists so you can `grep` and `read` the old implementation while migrating a feature — when an `src/Service/<Domain>/` placeholder needs filling in, the matching `gewisweb-laminas/module/<Domain>/` directory is the canonical "what did the old version do".

- After a fresh clone: `git submodule update --init` to populate it.
- **Strictly read-only.** Never edit anything under `gewisweb-laminas/`, never wire its paths into live code (autoload, config, routes, templates), and never run `composer`/`npm` inside it. It is reference material, not a source root.
- Tooling already ignores it: `.dockerignore` excludes the directory, and PHPStan, PHPCS, and Igor scan explicit paths (`src/`, `config/`, …) so the submodule is invisible to the quality gates. No action needed when you add files elsewhere.

## Stack

- **PHP 8.5** with `declare(strict_types=1)` required in every file.
- **Symfony 8.0.*** across the board (framework-bundle, security-bundle, messenger, scheduler, asset-mapper, stimulus, ux-live-component, etc.).
- **Doctrine ORM 3** with attribute mapping. Patched at install for sub-decision joins — see `composer.json` `extra.patches`.
- **Doctrine Migrations** — schema files live at repo-root `migrations/`. Migrations run automatically (one-shot) on container start, so there is no `make migrate` target; if you need to run them by hand, use `make sf c=doctrine:migrations:migrate`.
- **FrankenPHP worker mode** (Dockerfile `FROM dunglas/frankenphp`). Worker-mode safety is non-negotiable here — see the Care section below.
- **API Platform 4** (`config/packages/api_platform.yaml`, all stateless). `src/ApiResource/` is currently empty pending migration of the Laminas-era REST surface.
- **Symfony Messenger** over RabbitMQ in dev, in-memory in test (`config/packages/messenger.yaml`). **Scheduler** in `src/Scheduler/`.
- **Symfony Workflow** drives the revision/approval lifecycle (`config/packages/workflow.yaml`) — see the Revision workflow section.
- **Twig + Stimulus + Live Components**, Bootstrap 5, Font Awesome 7, Sass via `symfonycasts/sass-bundle`, asset-mapper. Stimulus controllers are **TypeScript**, compiled by `sensiolabs/typescript-bundle` (SWC) — see the Frontend assets section.
- **Altcha** (`tito10047/altcha-bundle`, `config/packages/altcha.yaml`) — self-hosted proof-of-work captcha on public forms (external activity sign-up, password-reset request). `App\Service\Application\AltchaSolutionGuard` blocks replay of solved challenges.
- **Mercure** for SSE; **Mailer** with MailPit in dev; **Notifier**; **Matomo** analytics; **scheb/2fa** for MFA.
- Runs under Docker via `docker compose`; most `make` targets shell into the `web` container.

## Module layout

Most folders under `src/` are split by domain (Activity, Career, Decision, Education, Frontpage, Photo, User, …): `Command/`, `Controller/`, `DataFixtures/`, `Entity/`, `EventListener/`, `Form/`, `Message/`, `MessageHandler/`, `Repository/`, `Security/`, `Service/`, `ViewModel/`. `Scheduler/`, `Twig/`, and `Workflow/` are flat; `ApiResource/` is empty for now. When adding to a domain, follow the sibling pattern — don't invent a new home.

```
src/
  ApiResource/      API Platform resources will live here
  Command/          #[AsCommand]-tagged console commands
  CommonMark/       Markdown rendering extensions
  Controller/       feature controllers
  DataFixtures/     seed data (member 8000 / password "gewiswebgewis")
  Doctrine/         Doctrine infrastructure (custom types, functions, …)
  Entity/           Doctrine entities with attribute mapping; some domains also have Enums/ and Traits/ subfolders (e.g. Entity/User/)
  EventListener/    #[AsEventListener] listeners (incl. workflow guards/transition listeners)
  Form/             Symfony Form types
  Kernel.php
  Message/          Messenger message classes
  MessageHandler/   #[AsMessageHandler] handlers
  Repository/       Doctrine repositories
  Scheduler/        Symfony Scheduler providers (flat)
  Security/         UserChecker, voters (SudoVoter, RevisionVoter), remember-me handler
  Service/          domain services (Decision/Education/Photo are still empty placeholders)
  Twig/             Extensions/ for custom Twig extensions; Components/<Namespace>/ for Live components (mirror src/Controller layout — e.g. User/Admin/UsersOverview)
  Validator/        custom constraint validators
  ViewModel/        immutable read models for templates, mirrors domain structure (e.g. ViewModel/Activity/Admin/SignupAdminRow)
  Workflow/         revision-workflow plumbing: marking store, RevisionCloner implementations + registry
config/             framework + per-bundle config under packages/
migrations/         Doctrine migration files at repo root
templates/          Twig templates, mirrors src/Controller layout; components/<Namespace>/ holds Live component templates, partials/application/ holds stateless includes
translations/       .xlf files for en/nl
assets/             TypeScript Stimulus controllers, Sass, vendored assets
tests/              bootstrap.php + object-manager.php only — no tests yet
gewisweb-laminas/   git submodule: pre-migration Laminas codebase, read-only reference
```

Prefer passing a `ViewModel` to a template over handing it entities or loose arrays when the view needs derived/aggregated data — see `src/ViewModel/Activity/` for the convention (final readonly classes, no behaviour beyond accessors).

## Revision workflow

Activities, companies, and vacancies are created and edited through a revision-based approval workflow. This is the dominant pattern on the current branch — read it before touching any of those domains.

- **Contracts** in `src/Entity/Application/`: `RevisableInterface` (the stable aggregate: `Activity`, `Career\Company`, `Career\Vacancy` — identity, revision chain, `markRevisionLive()`), `RevisionInterface` / `AbstractRevision` (a MappedSuperclass snapshotting all editable content per revision: `ActivityRevision`, `CompanyRevision`, `VacancyRevision`), and `Enums/RevisionStatus`.
- **Lifecycle** (`config/packages/workflow.yaml`, state machine `revision`): draft → submitted → in-review → { approved | changes-requested | rejected → closed }. The marking store is custom (`App\Workflow\RevisionStatusMarkingStore`, bridges the status enum). Only a *draft* revision is mutable; approval promotes it to the live revision.
- **Guards & side effects** are event listeners, not workflow config: `EventListener/Application/RevisionGuardListener` delegates to `App\Security\Application\RevisionVoter` (organ/creator/company scoping); `SpawnNextDraftListener` clones a new draft after changes-requested; `PromoteLiveRevisionListener` / `MigrateSignupsOnApprovalListener` run on approval.
- **Cloning**: each domain implements `App\Workflow\RevisionClonerInterface` (deep copy, bump revision number, link `previousRevision`), routed through `RevisionClonerRegistry`. A new revisable domain adds a cloner — never bespoke copy logic.
- **Edit locking**: `Entity/Application/EditLock` + `Service/Application/EditLockService` give one user at a time exclusive editing (90-second ping TTL, serialized via MariaDB `GET_LOCK`, reviewers can force-take). The `application/edit_lock_controller.ts` Stimulus controller does the pinging.
- **Activity sign-ups** are versioned with the revision: each `ActivityRevision` owns clones of its `SignupList`s, matched across revisions by `lineageId`; on approval `Service/Activity/SignupListMigrator` carries live sign-ups over (and guards block approval if migration would lose data). All sign-up writes go through `Service/Activity/SignupManager` — members sign up directly, external guests get double-opt-in email verification plus Altcha.

## Routing & locale

The site is bilingual (`en`, `nl`) and most controller routes are prefixed from `config/routes.yaml`:

- The `localised_routes` resource attribute-scans `src/Controller/` and applies `/en` and `/nl` prefixes. Controllers themselves use `#[Route]` attributes with locale-agnostic paths.
- **`page_route`** (custom-pages catch-all) and **`catch_all`** (404 fallback to `FrontpageController::notFound`) are defined in `config/routes.yaml` rather than as attributes — **order matters**. Attribute scanning that ran before the explicit YAML routes used to steal traffic; that bug recently bit voting committees. Don't reorder this file lightly.
- Other YAML route files under `config/routes/`: `api_platform.yaml`, `scheb_2fa.yaml`, `security.yaml`, `framework.yaml`, `nelmio_security.yaml`, `ux_live_component.yaml`, `web_profiler.yaml` (dev).
- `src/EventListener/Application/LocaleRedirectListener.php` redirects bare `/` to the user's preferred locale, falling back to `%kernel.default_locale%` (= `en`).
- `$defaultLocale` and `$supportedLocales` are auto-bound in `config/services.yaml` — services that need them can just declare the parameter.
- **Live-component routes are locale-prefixed** (`config/routes/ux_live_component.yaml` → `/{_locale}/_components` with `_locale: '%app.locales%'`). Without the locale segment, action POSTs would have no `_locale` route attribute and re-renders would always come back in the framework default (`en`) — there is no `LocaleSubscriber` syncing the session locale to fall back on.

## Twig components & partials

Two template locations, deliberately distinct:

- **`templates/components/<Namespace>/`** holds Twig / Live component templates, each paired with a backing PHP class in `src/Twig/Components/<Namespace>/`. Component names use `:` separators (e.g. `User:Admin:UsersOverview`); set them explicitly via `#[AsLiveComponent(name: ..., template: ...)]` so renames stay obvious. The namespace structure should mirror `src/Controller/` (e.g. `User/Admin/`, not flat `Admin/`) so admin tooling stays grouped with the rest of the domain.
- **`templates/partials/application/`** holds stateless `{% include %}` fragments — sidebars, pagination, sort headers, etc. Anything reused by multiple components belongs here, not co-located inside `templates/components/`. Files use kebab-case without a leading underscore (`pagination.html.twig`, `sort-header.html.twig`).

Render components from a regular template with `{{ component('User:Admin:UsersOverview') }}` (or `<twig:User:Admin:UsersOverview />`). Live-component action handlers re-render the component on the server, so any locale-dependent output (translations, date formatting) depends on the locale-prefixed route above.

## Frontend assets

- **Stimulus controllers are TypeScript.** They live in `assets/controllers/<domain>/` (`activity/`, `application/`, …) and are named `*_controller.ts`. Write new controllers in TypeScript — never plain JS. The single exception is `assets/controllers/csrf_protection_controller.js`, which is recipe-managed by Symfony Flex; leave it as JS so recipe updates apply cleanly.
- TS is compiled by `sensiolabs/typescript-bundle` via SWC (`.swcrc`, wired in `config/packages/asset_mapper.yaml`). **SWC strips types but does not type-check** — there is no `tsc` or eslint gate, so type errors surface at runtime. Be precise with DOM/Stimulus typings and verify behaviour in the browser (see Validating changes below).
- Sass lives in `assets/styles/`; third-party JS is vendored under `assets/vendor/` (asset-mapper, no `package.json`/npm). In dev, the entrypoint watches `assets/` and recompiles automatically.

## Security & users

Four firewalls in `config/packages/security.yaml`:

| Firewall | Pattern | User provider | Stateless | Notes |
|---|---|---|---|---|
| `dev` | `^/(_(profiler\|wdt)\|css\|images\|js)/` | — | — | dev assets |
| `api` | `^/api` | — | **yes** | stateless API surface |
| `company` | `^/(en\|nl)/company` | `company_user_provider` | no | form_login + throttling + 2FA |
| `main` | `^/` | `user_provider` | no | form_login + throttling + 2FA, `UserChecker` |

Two independent user entities:

- `App\Entity\User\User` — members, keyed by `lidnr` (membership number); roles derived from member type, explicit `UserRole` rows, and self-assigned role.
- `App\Entity\User\CompanyUser` — corporate users, keyed by company id; always `ROLE_COMPANY_USER`.

Both implement `Scheb\TwoFactorBundle\Model\TwoFactorInterface` (TOTP + backup codes). `App\Security\User\UserChecker` blocks `User` login for deleted / hidden / expired members or members without an email.

Authorization beyond roles: custom voters live under `src/Security/`. Notably, `App\Security\User\SudoVoter` gates destructive actions behind a 10-minute time-bounded `SUDO` grant (see `SudoMode`).

Remember-me is a **custom** integration (`App\Security\User\PersistentSignatureRememberMeHandler`, persisted via the `Session` entity) — read `config/packages/security.yaml` and `config/packages/session.yaml` before touching anything in this area. The cookies, lifetimes, and per-firewall handlers differ deliberately.

## Dependency injection

Pure autowire / autoconfigure from `src/`. There are **no factory classes** — constructor property promotion does all the wiring. Exclusions in `config/services.yaml`: `src/DependencyInjection/`, `src/Entity/`, `src/Security/User/HandlerRegistry.php`. `$defaultLocale` and `$supportedLocales` are auto-bound parameters.

Don't introduce factory classes here. That's a Laminas idiom and has no place in this codebase. If autowire can't resolve a dependency, define the service explicitly in `config/services.yaml`.

## Coding style

- `declare(strict_types=1);` immediately after the opening `<?php`.
- Constructor property promotion everywhere; many service-like classes are `final readonly`.
- Native PHP type hints on parameters, return types, and properties wherever a parent signature allows.
- Yoda-style comparisons: `null === $x`, `true === $foo->getDeleted()`.
- Multi-clause `if` conditions split one clause per line:
  ```php
  if (
      $a
      && $b
  ) {
  ```
- Attribute-based wiring throughout: `#[Route]`, `#[AsEventListener]`, `#[AsCommand]`, `#[AsMessageHandler]`. Use `#[Override]` on inherited methods.
- Follow `GEWISPHPCodingStandards` (the rule set in `phpcs.xml.dist`). `make lint` is authoritative; `make lint-fix` autofixes a subset.
- Doctrine entities use attribute mapping, not annotations or XML. Import each attribute by its short name (`use Doctrine\ORM\Mapping\Entity;`, `use Doctrine\ORM\Mapping\Column;`) and write `#[Entity]`, `#[Column]` — **not** the `ORM\` alias form (`#[ORM\Entity]`, `#[ORM\Column]`).
- Match the existing module's style when in doubt; consistency with surrounding code beats stylistic preferences.

## Static analysis & tests

Run these inside the `web` container (the `make` targets handle that for you). **`make lint` (after `make lint-fix`), `make phpstan`, and `make psalm` must pass for any change** — they are not optional; do not claim work done while one of them fails. Add `make lint-twig` whenever templates changed and `make igor` for any non-trivial change.

| Command | What it does |
|---|---|
| `make lint` / `make lint-fix` | PHPCS / PHPCBF against `GEWISPHPCodingStandards`. Run `lint-fix` first, then `lint` must be clean. |
| `make phpstan` | PHPStan analysis (baseline in repo root). Must pass. |
| `make psalm` / `make psalm-all` | Psalm with and without `psalm-baseline.xml`. `make psalm` must pass. |
| `make lint-twig` | `lint:twig` over `templates/` — validates Twig syntax. Run whenever you add or edit a template. |
| `make igor` | Validates the codebase for FrankenPHP worker-mode safety. **Run this for any non-trivial change.** |
| `make test` | PHPUnit inside the `web` container under `APP_ENV=test`. Note: `tests/` is currently almost empty — passing tests is a low bar today. |
| `make translations` | Extracts translatable strings into `translations/{messages,validators}.{en,nl}.xlf`. Run this whenever you add or edit a user-facing string in PHP, Twig, or a form type — never hand-roll `bin/console translation:extract`, the Makefile target sets the project's expected flags (`--sort=asc --no-fill --force --clean`). `--clean` removes entries no longer referenced in source — safe for the `validators` domain because Symfony falls back to the vendor `validators.{en,nl}.xlf` for any key not in the project file. **Extraction alone is not enough — see below.** |

**After `make translations`:** because of `--no-fill`, new entries land with an empty `<target/>` in *both* the `en` and `nl` files. Find them with

```sh
grep -rn -e '<target/>' -e '<target></target>' translations/
```

then fill every one: `en` targets get the source text verbatim; `nl` targets get your best Dutch translation. Always list the Dutch translations you wrote in your final report so a human can review (and correct) them — never leave empty targets behind silently.

When a new error hits a baseline, fix it rather than extending the baseline. Baselines are for legacy debt, not new code.

For form types: wrap user-facing labels and `invalid_message` strings with `t()` (`use function Symfony\Component\Translation\t;`). Symfony's PHP extractor does not recurse into `RepeatedType`'s `first_options` / `second_options`, so plain `'label' => 'My label'` strings nested there get silently skipped by `make translations`; `t('My label')` is always picked up. Don't eagerly call `$translator->trans()` at form-build time — it locks the locale before render and bypasses the form renderer's own translation pass.

## Local development workflow

- `make start` — build images, then `up` in detached mode.
- `make seed` — loads Doctrine fixtures. Seeded login: member `8000` / password `gewiswebgewis`. (Migrations themselves have already run by the time you get here — they fire one-shot on container start.)
- `make bash` — shell into the FrankenPHP `web` container.
- `make sf c=...` — run a Symfony console command (e.g. `make sf c=doctrine:migrations:migrate`).
- `make composer c='...'` — run Composer inside the container.
- `make cc` — clear the cache.
- `make stop` / `make logs` — as named.

(For `make translations`, `make lint`, `make igor`, etc. see the Static analysis & tests section above — those are quality gates, not just workflow conveniences.)

Hot reload covers almost everything in dev: FrankenPHP's `hot_reload` reloads PHP workers on source changes, and the dev entrypoint (`docker/web/docker-entrypoint.sh`) runs `sass:build --watch` plus an `inotifywait` loop that recompiles the asset map whenever `assets/` changes. You should very rarely need `make cc` or a container restart — reach for them only if something genuinely won't budge.

Other locally-exposed services (per `README.md`): phpMyAdmin on `:8080`, MailPit on `:8025`, RabbitMQ management on `:15672`, Matomo on `:82`.

### Validating changes in the browser

CSRF protection is **stateless** (`config/packages/csrf.yaml`: `stateless_token_ids: [submit, authenticate, logout]`) — validation relies on a double-submit cookie plus Origin/Referer checks, with `assets/controllers/csrf_protection_controller.js` generating the token client-side. A hand-crafted `curl` POST therefore fails CSRF by design. **Do not "verify" forms, login, or sign-up flows with curl** — a 4xx proves nothing about your change, and you cannot bypass the protection.

Instead, validate interactive flows in a real browser with Playwright (use your own browser tooling — there is no Playwright setup in this repo) against `http://localhost` (the `web` container publishes port 80). Seed first with `make seed`, then log in as member `8000` / password `gewiswebgewis`. Outbound mail is visible in MailPit on `:8025` (useful for external sign-up verification and password-reset flows).

## API Platform

Resources go in `src/ApiResource/` (currently empty). The `api` firewall is stateless and matches `^/api`; config in `config/packages/api_platform.yaml`. As the Laminas-era REST surface is migrated, new resources land here as attribute-decorated classes.

## Messaging & scheduling

Messenger is backed by RabbitMQ in dev (`AMQP_DSN`), in-memory in test. Buses, transports, and routing are in `config/packages/messenger.yaml`. Message classes go in `src/Message/`; handlers in `src/MessageHandler/` with `#[AsMessageHandler]`. Recurring work runs through `src/Scheduler/` (Symfony Scheduler).

## GEWISDB integration

GEWISDB is the sister project — the canonical decision and membership database. This codebase does **not** call its HTTP API from `src/`; sync happens externally. When you edit anything that mirrors a GEWISDB shape — notably `src/Entity/Application/Enums/Languages.php`, which has an inline warning, and most things in `src/Entity/Decision/` — keep the two sides in lockstep, or the next sync run breaks.

`App\Command\Decision\ImportGewisdbCommand` runs the sync twice an hour (`28,58 * * * *`). It writes via **raw DBAL upserts**, not the ORM, so any Doctrine-managed cache (second-level cache, identity map) must be invalidated by hand — which the command does for `member_region` on success. If you add SLC to another entity that the sync writes to, add the matching `evictEntityRegion()` call there too.

## Doctrine caching

`App\Entity\Decision\Member` is the only entity in the second-level cache (`config/packages/doctrine.yaml` → `second_level_cache`, region `member_region`, 30-min TTL, backed by `cache.app`). Member is read everywhere (sign-up lists, photo tags, decision rendering, …) and only changes at sync time, so by-ID loads (`$em->find(Member::class, $lidnr)`, lazy associations) serve from cache. DQL queries that select Members still hit the database unless explicitly marked cacheable.

Deliberately **not** cached:

- `User` — MFA enrolment toggles must take effect on the next request.
- `UserRole` — admin grants/revocations need to be visible immediately.

Cache strategy is `READ_ONLY`. Member is never mutated through the ORM (only inserted by fixtures and upserted by the raw-DBAL sync), so `READ_ONLY` doubles as a guardrail: any future ORM `update`/`remove` on Member will throw `CannotUpdateReadOnlyEntityException`, forcing a deliberate decision (either route the write through raw DBAL + manual eviction, or move the entity to `NONSTRICT_READ_WRITE`). For new cached entities, pick `READ_ONLY` if the same "writes only via DBAL or fixtures" property holds; otherwise use `NONSTRICT_READ_WRITE`.

## Things to be careful about

- **FrankenPHP worker mode.** Avoid static state, lazy-singleton patterns, mutable globals, and runtime container mutation — these survive across requests in worker mode and cause subtle leaks. Run `make igor` before claiming work done. (You don't normally need to restart the worker after edits — `hot_reload` handles it.)
- **Bilingual route ordering.** `page_route` and `catch_all` in `config/routes.yaml` are sensitive to attribute-scan order. Don't reorder this file lightly — that's how the recent voting-committees 404 bug happened.
- **Two user entities.** Any new authorization logic must reason about both `User` and `CompanyUser`. Don't assume `$this->getUser()` returns a member.
- **Custom remember-me.** The per-firewall handlers, cookie names, and lifetimes are deliberate (90 days for members, 7 days for companies). Read `security.yaml` and `session.yaml` before touching them.
- **Decisions are historical record.** Don't "fix" past data by editing decision rows; model corrections as new decisions / sub-decisions. Same rule as GEWISDB.
- **Non-draft revisions are immutable.** Only a `draft` revision may be edited; everything after submission is history. To change approved content, spawn a new draft via the cloner registry and run it through the workflow — never mutate a submitted/approved `*Revision` row directly.
- **CSRF can't be curl'd.** Stateless CSRF + Origin checks mean scripted POSTs fail by design — validate flows with Playwright in a real browser (see Validating changes in the browser).
- **Empty placeholder directories** (`src/Service/<Domain>/`, `tests/`, `src/ApiResource/`) reflect in-progress migration state. If you need a service for a domain, that empty folder is the right home — don't invent a new convention.
- **No factories.** Never introduce factory classes; this is a Symfony codebase now.
- **Build artifacts.** Treat `vendor/`, `var/`, `public/build/`, `public/assets/` as read-only.
- **Commits.** Follow conventional-commit prefixes (`feat:`, `fix:`, `chore:`) — visible in recent `git log`.

## When you don't know

Read the nearest sibling: the existing controllers, services, and listeners in the same domain folder are the canonical reference. When migrating a Laminas-era feature, the matching `gewisweb-laminas/module/<Domain>/` directory is the canonical "what did the old version do" — read it for behaviour, but do not port code verbatim. If the question is genuinely unclear — especially anything touching auth, routing order, or GEWISDB-shaped data — ask before guessing.
