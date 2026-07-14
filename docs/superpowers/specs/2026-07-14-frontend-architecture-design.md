# Frontend Architecture (Symfony UX) — Design

**Date:** 2026-07-14
**Status:** Approved

## Goal

Give the app a clean, neat UI and — more importantly — a front-end architecture with
explicit rules, so templates stay ordered, reusable, and consistent as the project grows.

## Decisions

| Decision | Choice |
|---|---|
| Component system | symfony/ux-twig-component: anonymous Twig components (HTML syntax `<twig:...>`); PHP-class components only when logic is needed |
| Live Components | symfony/ux-live-component installed and configured, but **zero usages in v1** — first Live Component only when a real need appears |
| CSS | Tailwind via symfonycasts/tailwind-bundle (standalone CLI, no Node); design tokens in `@theme` in app.css |
| Component taxonomy | Two tiers only: shared UI kit in `templates/components/` + pages that compose components (no atomic design) |
| Aesthetic | Light, neutral zinc palette + indigo accent; statuses: draft = amber, published = emerald; system font stack; centered `max-w-5xl` content; cards with subtle shadows |
| Scope | Whole app: base layout, course pages, login/registration |

## Directory structure

```
assets/
├─ styles/app.css              # @import "tailwindcss" + @theme (design tokens)
├─ controllers/                # Stimulus: one behavior = one controller
└─ app.js
templates/
├─ base.html.twig              # skeleton: <twig:Layout:Nav>, <twig:Layout:Flashes>, block body
├─ components/                 # UI KIT — the ONLY place with Tailwind classes
│  ├─ Button.html.twig         #   <twig:Button variant="primary|danger|ghost" href?>
│  ├─ Alert.html.twig          #   <twig:Alert type="success|error|warning">
│  ├─ Badge.html.twig          #   <twig:Badge status="draft|published">
│  ├─ Card.html.twig           #   padded container with shadow, content slot
│  ├─ Field.html.twig          #   form_row wrapped in consistent field layout
│  ├─ PageHeader.html.twig     #   <twig:PageHeader title="..."> + actions slot
│  ├─ EmptyState.html.twig     #   empty list with icon and CTA
│  └─ Layout/
│     ├─ Nav.html.twig         #   top nav + auth state (includes CSRF-fixed logout form)
│     └─ Flashes.html.twig     #   loops app.flashes → <twig:Alert>
├─ course/  security/  registration/   # pages: composition of components ONLY
└─ form/theme.html.twig        # Symfony form theme built on Tailwind classes
```

## Rules (the clean-front contract)

1. **Utilities only inside components.** Tailwind classes may appear only in
   `templates/components/` and `form/theme.html.twig`. Pages use `<twig:Card>`, never
   `class="rounded-xl border ..."`. Violation is detectable with a grep for `class="` in
   page directories.
2. **Props are a contract.** A component declares its props at the top
   (`{% props variant = 'primary', href = null %}`) and never reaches into page context.
   Everything it needs comes explicitly — like function arguments.
3. **No domain logic in Twig.** Templates may iterate and compare prop strings; they may
   not compute, filter collections, or know business rules (when a course may be
   published is decided by the controller/read model, not the template).
4. **Components are extracted on demand, not up front.** Something becomes a component
   when (a) it is used a second time, or (b) it has a meaningful props contract.
   A fragment of one page stays on that page.
5. **Stimulus = behavior, not content.** A controller does one DOM behavior (e.g.
   `clipboard`, `confirm`), named after the behavior, never after a page. Content is
   always server-rendered (Twig/Turbo); JS never draws data.
6. **Turbo-first.** Navigation and forms go through Turbo Drive with no custom JS; code
   must survive the absence of full page loads (document-level listeners, idempotent
   controllers — the lesson from the CSRF bug).
7. **Tokens over values.** Colors/spacing only via the Tailwind scale and `@theme` —
   `text-accent`, not `text-[#4f46e5]`. Arbitrary values (`w-[137px]`) are forbidden.
8. **Forms through the theme.** Field appearance is defined once in
   `form/theme.html.twig`; pages call `form_row`/`<twig:Field>`. No per-page field
   styling.
9. **Accessibility by default.** Components carry semantics: `Alert` has `role="alert"`,
   `Field` binds label to input, focus rings come from tokens — pages get a11y for free.
10. **Naming.** Components `PascalCase` (subfolder = namespace: `Layout:Nav`), Stimulus
    controllers `kebab-case`, props `camelCase`.

## Design tokens (@theme in app.css)

- Palette: zinc neutrals; accent indigo (`--color-accent-*`); status colors:
  draft = amber, published = emerald; error/success/warning aligned with flash types.
- Typography: system font stack (no webfonts); type scale from Tailwind defaults.
- Spacing/radius: Tailwind default scale; shared radius token for cards/buttons/inputs.

## Page mapping

| Page | Composition |
|---|---|
| `base.html.twig` | `Layout:Nav` + `Layout:Flashes` + centered `<main>` |
| `course/index` | `PageHeader` (title + `Button` "New course") → grid of `Card`s with title, status `Badge`, date; empty list → `EmptyState` |
| `course/show` | `PageHeader` (title + `Badge` + actions: rename/publish `Button`s) → `Card` with description and metadata |
| `course/new`, `course/rename` | `PageHeader` → `Card` with form (theme + `form_row`), submit `Button` |
| `security/login`, `registration/register` | narrow (`max-w-sm`) centered `Card`, fields via theme, cross links |

`course/_flashes.html.twig` is removed — `Layout:Flashes` in `base` replaces the
per-page includes (deduplication). The logout form moves into `Layout:Nav` together with
its `data-controller="csrf-protection"` CSRF fix.

## New dependencies

- `symfony/ux-twig-component` — runtime.
- `symfony/ux-live-component` — runtime (configured, unused in v1).
- Tailwind standalone binary via existing `symfonycasts/tailwind-bundle`
  (`tailwind:init` still to be completed; downloads the binary).

## Implementation order

1. Finish `tailwind:init`; define tokens in `@theme` in `app.css`.
2. Install and configure ux-twig-component + ux-live-component.
3. Build the UI kit (`templates/components/`).
4. Build the form theme (`form/theme.html.twig`, registered in `twig.yaml`).
5. Rewrite pages as component compositions.
6. Remove `course/_flashes.html.twig`.

## Verification (no tests, per project rule)

- `php bin/console tailwind:build` succeeds.
- `lint:twig` and `lint:container` pass.
- Grep `class="` over `templates/course|security|registration` returns nothing —
  proves rule 1 holds.
- Visual check via `symfony serve` on the user's side.

## Out of scope (v1)

- Any Live Component usage (search/filter on the course list is the designated first
  candidate when needed).
- Dark mode.
- Webfonts, icon libraries (inline SVG only where needed).
- Restyling Symfony error pages / profiler.
