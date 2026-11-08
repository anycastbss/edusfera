# Edusfera

Edusfera is a modern Laravel 12 SaaS platform for tutors and exam preparation (CT/CE in Belarus). The platform operates on a **SaaS subscription model for tutors with 0% commission on conducted lessons**, providing an interactive Virtual Classroom, smart scheduling, automated NPD receipt generation (for self-employed tutors), AI diagnostic tools, and direct peer-to-peer student settlements (ERIP / bank card / cash) with zero commission on tutor earnings.

## Stack

- PHP 8.3
- Laravel 12
- Filament 3
- PostgreSQL 16
- Redis
- Mailhog
- Vite 7 + Tailwind CSS v4 + React 19 (Virtual Classroom LiveKit & Excalidraw)

## Business Model & Main Flows

- **Tutor SaaS Subscriptions & Anti-Crisis Offers:**
  - **Test-Drive (7 days):** 5 BYN instant payment for full Pro features (AI lesson generator, LiveKit virtual classroom, NPD receipts in 1-click).
  - **Founders Club (3 months Pro):** 49 BYN one-time (60% discount, limited to 10 spots) with lifetime Founder status badge.
  - **Standard Plans:** Basic (20 BYN/mo), Pro (40 BYN/mo), Premium (60 BYN/mo White-Label). Unbacked free trials without card binding are restricted to maintain service quality and prevent spam.
- **0% Lesson Commission & Direct Settlements:** Tutors keep 100% of their hourly rates. Students pay tutors directly (cash, ERIP, card transfer) without platform escrow, intermediate transit, or balance hold.
- **Automated NPD Tax Receipts:** Generation of fiscal receipts and income registries for the Belarusian Ministry of Taxes and Duties ("ProfDohod" app).
- **Interactive Virtual Classroom & 1-Click Guest Join:** Fullscreen LiveKit WebRTC video call + shared Excalidraw whiteboard. Tutors can schedule lessons for unregistered students via signed instant link (`/classroom/join/{lesson}?token=...`) with no login or install required.
- **Tutor Catalog & Trajectories:** Filterable public tutor catalog, diagnostics, and structured lesson packages.
- **Tutor & Student Dashboards:** Full-featured management portals powered by Filament.

## Local Run

1. Install dependencies:

```bash
composer install
npm install
```

2. Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Start infrastructure:

```bash
docker compose up -d
```

4. Run migrations:

```bash
php artisan migrate
```

5. Start development processes:

```bash
composer run dev
```

After startup:

- app: `http://127.0.0.1:8000`
- Vite: `http://localhost:5173`
- Mailhog: `http://127.0.0.1:8025`
- admin login: `http://127.0.0.1:8000/admin/login`
- site admin login: `http://127.0.0.1:8000/site-admin/login`

## Tests

Run:

```bash
php artisan test
```

The test suite uses SQLite in memory via `phpunit.xml`, while local runtime uses PostgreSQL from `.env`.

### Architecture tests (layer isolation)

Architectural boundaries (Requirement 14 of the `microservices-foundation` spec) are
enforced by `tests/Architecture/LayerIsolationTest.php` and exposed as a dedicated
PHPUnit testsuite:

```bash
php artisan test --testsuite=Architecture
# or
make arch-test
```

The test analyses source files via PHP tokenization (no extra dependencies, no Pest)
and fails the build if any of these boundaries are crossed:

- `app/Http/Api/V1/*` must not reference `App\Models\*` directly (read the domain through DTOs/contracts);
- `app/Filament/*` must reach the extracted Lesson domain through `App\Contracts\*`, not through `App\Services\Lesson\*` implementations;
- `app/Domain/*` must not depend on `App\Filament\*` or `App\Http\*`;
- `app/Http/Webhooks/*` must not call the `DB` facade or touch Eloquent models directly.

CI runs this suite on every push/PR via `.github/workflows/architecture-tests.yml`.

## Public Pages

- `/` - landing page
- `/tutors` - public tutor catalog
- `/for-tutors` - landing page for tutors
- `/contacts` - support and contact page

## Legal Pages

- `/offer` - public offer (SaaS terms & 0% lesson commission)
- `/payment-security` - payment security & bank rules
- `/refund-policy` - refund policy & SaaS guarantee
- `/privacy-policy` - privacy policy

## Support

- public support entrypoint: `/contacts`
- default support email: `MAIL_FROM_ADDRESS`

## Project Notes

- Payment processing is mocked in local development.
- Technical admin credentials are configured through `.env`.
- UI rules for all new screens are documented in [`docs/UI_RULES.md`](docs/UI_RULES.md).
