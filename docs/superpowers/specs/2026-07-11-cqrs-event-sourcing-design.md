# CQRS + Event Sourcing Foundation — Design

**Date:** 2026-07-11
**Status:** Approved

## Goal

Introduce a CQRS + Event Sourcing architecture into this Symfony 8.1 skeleton, organized
by DDD bounded contexts, and prove it with a first vertical slice: the Course lifecycle
of a course platform.

## Decisions

| Decision | Choice |
|---|---|
| CQRS flavor | Full event sourcing: aggregates persist as event streams |
| ES infrastructure | Hand-rolled (no EventSauce/Ecotone) on PostgreSQL + Doctrine DBAL |
| Buses | Symfony Messenger: `command.bus`, `query.bus`, `event.bus` |
| Structure | Context-first: `src/<Context>/{Domain,Application,Infrastructure,Presentation}` + `src/Shared` |
| Projections | Hybrid: critical projectors sync (same transaction), others async (Doctrine transport) |
| Presentation | Server-rendered Twig + Symfony Form (existing UX/Turbo stack) |
| First slice | Course lifecycle: create, rename, publish; list + detail read models |

## Architecture

### Directory layout

```
src/
├─ Shared/
│  ├─ Domain/
│  │  ├─ AggregateRoot.php        # recordThat(), releaseEvents(), reconstitute from history
│  │  ├─ DomainEvent.php          # interface: aggregateId(), occurredAt(), toPayload(), fromPayload()
│  │  ├─ AggregateId.php          # abstract UUIDv7 value object base
│  │  └─ Clock.php                # PSR-20-style interface for testable time
│  ├─ Application/
│  │  ├─ Command.php, Query.php   # marker interfaces
│  │  ├─ CommandBus.php           # interface: dispatch(Command): void
│  │  └─ QueryBus.php             # interface: ask(Query): mixed
│  └─ Infrastructure/
│     ├─ MessengerCommandBus.php  # adapter over MessageBusInterface; unwraps HandlerFailedException
│     ├─ MessengerQueryBus.php    # adapter using HandleTrait
│     ├─ EventStore/
│     │  ├─ EventStore.php        # interface: append(id, type, expectedVersion, events), load(id)
│     │  ├─ DbalEventStore.php    # PostgreSQL implementation
│     │  ├─ EventTypeRegistry.php # stable name ('course.created.v1') ⇄ FQCN
│     │  ├─ Upcaster.php          # payload-migration hook; default no-op passthrough
│     │  └─ ConcurrencyException.php
│     └─ SystemClock.php
├─ Course/                        # first bounded context (see below)
└─ Kernel.php
```

### Message buses

Three Messenger buses configured in `config/packages/messenger.yaml`:

- `command.bus` (default bus) — `doctrine_transaction` middleware; exactly one handler
  per command; returns nothing.
- `query.bus` — no transaction middleware; exactly one handler per query; returns a DTO.
- `event.bus` — `allow_no_handlers: true`; zero or more handlers (projectors) per event.

Application code depends only on our own `CommandBus`/`QueryBus` interfaces; the
Messenger adapters are the single framework touchpoint. Domain classes (aggregates,
events, value objects) contain no framework code at all.

### Event store

One append-only table, created by a Doctrine migration:

```sql
event_store (
  sequence       BIGSERIAL PRIMARY KEY,     -- global order for future replay/catch-up
  aggregate_id   UUID NOT NULL,
  aggregate_type VARCHAR(100) NOT NULL,
  version        INT NOT NULL,              -- per-aggregate, starts at 1
  event_type     VARCHAR(150) NOT NULL,     -- stable name, e.g. 'course.created.v1'
  payload        JSONB NOT NULL,
  occurred_at    TIMESTAMPTZ NOT NULL,
  UNIQUE (aggregate_id, version)            -- optimistic concurrency guard
)
```

- `append()` inserts new events at `expectedVersion + 1..n` within the surrounding
  command transaction. A unique-constraint violation is translated to
  `ConcurrencyException`.
- `load()` returns the ordered history; the repository replays it through the
  aggregate's `apply*` methods to rebuild state.
- Serialization is explicit: every event implements `toPayload(): array` and
  `static fromPayload(array): self`. The `EventTypeRegistry` maps stable string names
  to classes so class renames never corrupt streams. Event names carry a version
  suffix (`.v1`); the `Upcaster` interface sits between raw rows and `fromPayload()`
  to migrate old payload shapes when events evolve.

## Course context (first vertical slice)

```
src/Course/
├─ Domain/
│  ├─ Course.php                  # aggregate
│  ├─ CourseId.php                # extends AggregateId
│  ├─ CourseStatus.php            # enum: Draft | Published
│  ├─ Event/
│  │  ├─ CourseCreated.php        # 'course.created.v1'
│  │  ├─ CourseRenamed.php        # 'course.renamed.v1' (title + description)
│  │  └─ CoursePublished.php      # 'course.published.v1'
│  ├─ CourseRepository.php        # interface: get(CourseId): Course, save(Course): void
│  └─ Exception/                  # CourseNotFound, CourseAlreadyPublished, ...
├─ Application/
│  ├─ Command/
│  │  ├─ CreateCourse.php  + CreateCourseHandler.php
│  │  ├─ RenameCourse.php  + RenameCourseHandler.php
│  │  └─ PublishCourse.php + PublishCourseHandler.php
│  └─ Query/
│     ├─ FindCourse.php    + FindCourseHandler.php     # returns CourseDetail DTO
│     └─ ListCourses.php   + ListCoursesHandler.php    # returns CourseListItem DTO[]
├─ Infrastructure/
│  ├─ EventStoreCourseRepository.php   # load/save via EventStore; publishes to event.bus
│  ├─ ReadModel/
│  │  ├─ CourseDetailProjector.php     # SYNC (detail page is read right after redirect)
│  │  ├─ CourseListProjector.php       # ASYNC (Doctrine transport)
│  │  └─ CourseReadModelRepository.php # DBAL reads over projection tables
└─ Presentation/
   └─ CourseController.php             # Twig pages + Symfony Forms
```

### Aggregate rules

- `Course::create(id, title, description)` → records `CourseCreated`.
- `rename(title, description)` → records `CourseRenamed`; **rejected on published
  courses** (`CourseAlreadyPublished`).
- `publish()` → records `CoursePublished`; **only from Draft status**
  (`CourseAlreadyPublished` otherwise).
- State mutation happens only in `apply*` methods, so replaying history and applying a
  new event share one code path.

### Data flow

**Write:** Controller builds command from form → `CommandBus->dispatch()` → handler
loads aggregate via `CourseRepository`, calls domain method, `save()` → repository
appends released events to the event store (inside the command transaction) and
dispatches them on `event.bus` → sync projectors update their tables in the same
transaction; async events are enqueued on the Doctrine transport and processed by
`messenger:consume`.

**Read:** Controller → `QueryBus->ask()` → query handler reads a projection table via
DBAL → immutable DTO → Twig. Queries never touch the event store or aggregates.

### Read model tables (plain migrations, denormalized, one per view)

- `course_detail (id, title, description, status, created_at, published_at)`
- `course_list  (id, title, status, created_at)`

Projectors are idempotent (upsert-style) so async retries are safe.

### Twig pages

- `GET /courses` — list (from `course_list`)
- `GET /courses/{id}` — detail (from `course_detail`)
- `GET|POST /courses/new` — create form
- `GET|POST /courses/{id}/rename` — rename form
- `POST /courses/{id}/publish` — publish button

Templates extend `base.html.twig`; forms use Symfony Form with CSRF; Turbo handles
navigation (no custom JS needed for this slice).

## Error handling & consistency

- **Input validation** at the edge via Symfony Form + Validator; malformed input never
  becomes a command.
- **Domain invariants** throw specific exceptions; `MessengerCommandBus` unwraps
  `HandlerFailedException` so controllers catch real domain exceptions and render form
  errors / flash messages.
- **Concurrency:** `ConcurrencyException` → user-facing "modified by someone else,
  please retry" flash. No automatic retry in v1.
- **Async failures:** existing retry policy (3 retries, exponential backoff), then the
  `failed` transport (`messenger:failed:*` commands) — already configured.
- **Atomicity (outbox for free):** the async transport is Messenger's Doctrine
  transport on the same PostgreSQL database, and command handlers run inside
  `doctrine_transaction` middleware — so event-store appends, sync projection updates,
  and async message enqueueing commit atomically.

## Testing

- **Aggregate unit tests** — pure given/when/then: given past events, when a method is
  called, then expect recorded events or a domain exception. A small
  `AggregateScenario` helper in `tests/` makes these read like specs. No DB, no
  framework.
- **Application tests** — handlers against an `InMemoryEventStore` double.
- **Integration tests** — `DbalEventStore` (append/load/concurrency/upcasting) and each
  projector against the real PostgreSQL test database.
- **Functional tests** — `WebTestCase` through the Twig pages end to end; async
  transport swapped to `in-memory://` under `when@test` so worker processing is
  deterministic in tests.
- Add `dama/doctrine-test-bundle` (dev) for per-test transaction rollback.
- PHPUnit 13 strict flags (fail on deprecation/notice/warning) stay on.

## Out of scope (v1)

- Snapshots (streams will be short; add later if replay cost ever matters).
- Event-stream replay / projection rebuild tooling (the `sequence` column keeps the
  door open).
- Security/authentication (bundle remains scaffolded but unconfigured).
- Lessons, enrollment, progress tracking — future slices/contexts.
- Cross-context integration events.

## New dependencies

- `symfony/uid` (UUIDv7 for aggregate ids) — runtime.
- `dama/doctrine-test-bundle` — dev.