# Arquitectura — Bioinfo

Documento de diseño técnico. Describe las decisiones de arquitectura, el modelo de datos, el sistema de bloques extensible y los flujos principales.

Complementa a [`README.md`](README.md) (setup) y [`BUSINESS_RULES.md`](BUSINESS_RULES.md) (reglas de negocio).

---

## Índice

1. [Objetivo y alcance del MVP](#1-objetivo-y-alcance-del-mvp)
2. [Diagrama de capas](#2-diagrama-de-capas)
3. [Decisiones técnicas y su porqué](#3-decisiones-técnicas-y-su-porqué)
4. [Modelo de datos](#4-modelo-de-datos)
5. [Flujo de render público (SSR) vs panel (CSR)](#5-flujo-de-render-público-ssr-vs-panel-csr)
6. [Sistema de bloques extensible](#6-sistema-de-bloques-extensible)
7. [Layout: lista vs grid (Bento)](#7-layout-lista-vs-grid-bento)
8. [Sistema de temas](#8-sistema-de-temas)
9. [Embeds y seguridad](#9-embeds-y-seguridad)
10. [Analíticas](#10-analíticas)
11. [Autorización y roles](#11-autorización-y-roles)
12. [Estructura de carpetas](#12-estructura-de-carpetas)
13. [Testing](#13-testing)
14. [Roadmap por fases](#roadmap-por-fases)

---

## 1. Objetivo y alcance del MVP

Bioinfo es un builder de páginas *link-in-bio* autohospedado y **multi-usuario** (roles `admin` y `user`). El principio rector es **"features sin límite"**: la arquitectura debe permitir agregar nuevos tipos de contenido, integraciones y capacidades **sin reescribir el núcleo** ni migrar el esquema cada vez.

Principios de diseño transversales:

- **Extensibilidad por datos, no por esquema.** Los bloques y los eventos de analytics usan un discriminador `type` + payload JSON. Agregar un tipo nuevo no requiere migración.
- **IDs internos + identificador público.** Se usa `bigint` autoincremental internamente y un `ULID` público (`public_id`) para URLs expuestas (redirección de clics, QR), evitando enumeración.
- **Escritura de analytics desacoplada** mediante colas, para no penalizar el render SSR de la página pública.
- **Todo lo "por página" vive en `pages`** (tema, layout, SEO), porque el roadmap contempla múltiples páginas por cuenta. El MVP crea 1 página por usuario, pero el modelo ya soporta N.

---

## 2. Diagrama de capas

```
┌──────────────────────────────────────────────────────────────┐
│  Navegador                                                     │
│   • Página pública  → HTML server-rendered (Inertia SSR)       │
│   • Panel / Admin   → Vue 3 (CSR) sobre Inertia                │
└───────────────┬──────────────────────────────┬────────────────┘
                │ Inertia (props JSON)          │
┌───────────────▼──────────────────────────────▼────────────────┐
│  Laravel 12                                                     │
│   Rutas (web.php) → Middleware (auth, role, registro) →         │
│   Controllers finos → FormRequests (validación) →               │
│   Actions / Services (lógica) → Policies (autorización)         │
└───────────────┬────────────────────────────────────────────────┘
                │ Eloquent + spatie/laravel-data (DTOs)
┌───────────────▼────────────────────────────────────────────────┐
│  MySQL 8   (+ colas en DB, media library en storage)            │
└─────────────────────────────────────────────────────────────────┘
```

- **Controllers finos**: reciben el request, delegan en **Actions** de una responsabilidad y devuelven una respuesta Inertia.
- **FormRequests**: validación de entrada (incluida la validación del payload de bloque por tipo).
- **DTOs (spatie/laravel-data)**: forma tipada y serialización limpia de los datos que viajan a Vue.

---

## 3. Decisiones técnicas y su porqué

| Decisión | Elección | Por qué |
|---|---|---|
| Puente front/back | **Inertia.js + Vue 3** (monolito) | El más productivo con el stack habitual (Laravel + Vue). Sin mantener una API separada en el MVP. SSR disponible para la página pública. |
| API separada | **No en el MVP** (sí en Fase 2 con Sanctum) | La API pública se agrega cuando haga falta (app móvil, integraciones), sin bloquear el MVP. |
| TypeScript | **Sí** | El registry de bloques y los temas son datos polimórficos; TS + discriminated unions previene errores al agregar tipos. |
| SEO / Open Graph | **Meta y OG server-side vía la root view de Inertia** (`$page['props']['meta']` en `app.blade.php`) | Los previews sociales funcionan en el HTML inicial sin necesidad de correr el servidor Node de Inertia SSR. El SSR completo (render del cuerpo) queda como mejora futura documentada. |
| Componentes UI | **shadcn-vue solo en el panel** | La página pública usa componentes propios mínimos: bundle liviano y tema 100% controlado por CSS variables. |
| Modelo de bloques | **Tabla única `blocks` con `type` + `data` JSON** | Ver [§6](#6-sistema-de-bloques-extensible). Habilita "tipos ilimitados" sin migraciones. |
| Roles | **spatie/laravel-permission** | Middleware y helpers listos; se amortiza con el área admin y el feature-gating futuro. |
| Starter kit | **Starter kit oficial de Laravel 12 para Vue** | Trae Inertia 2 + Vue 3 + TS + Tailwind v3.4 + shadcn-vue + auth completa + SSR ya cableado. |
| Analytics | **Eventos crudos + rollups diarios** | Ver [§10](#10-analíticas). Escala a analytics avanzadas sin rehacer el modelo. |

### Fase 0 — Scaffolding

El proyecto se inicia con el starter kit oficial de Laravel 12 para Vue (Inertia + TypeScript + Tailwind + auth + SSR). En un directorio vacío como `c:\laragon\www\bioinfo` se puede scaffoldear con el instalador de Laravel (`laravel new bioinfo --vue`) o `composer create-project`, seleccionando autenticación integrada (email/contraseña) y Pest para tests. A continuación se instalan los paquetes core (`spatie/laravel-permission`, `-medialibrary`, `-data`, `-sluggable`, `-settings`, `endroid/qr-code`) y se crean las migraciones base y los seeders.

---

## 4. Modelo de datos

### 4.1 `users`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| name | string | |
| username | string unique | **handle público** (`/{username}`); formato `^[a-z0-9_.-]{3,30}$`, único case-insensitive |
| email | string unique | |
| email_verified_at | timestamp null | |
| password | string | |
| is_active | boolean default true | el admin puede desactivar cuentas |
| remember_token, timestamps | | |

Los roles no van como columna: se gestionan con `spatie/laravel-permission` (tablas `roles`, `model_has_roles`, …). Roles sembrados: `admin`, `user`.

> El `username` vive en el usuario (es el handle de la cuenta). Cada `page` tiene un `slug`; la página primaria se sirve en `/{username}` y las secundarias (Fase 2) en `/{username}/{slug}`.

### 4.2 `pages`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK→users | index, cascade |
| public_id | ulid unique | referencia pública |
| slug | string | único por usuario (`unique(user_id, slug)`) |
| title | string null | |
| bio | text null | |
| theme | json null | `{ presetId, mode: 'light'\|'dark'\|'auto', tokens: {...overrides} }` |
| layout | enum('list','grid') default 'list' | lista (Linktree) vs grid (Bento) |
| meta_title | string null | SEO |
| meta_description | string null | SEO |
| status | enum('draft','published') default 'draft' | |
| is_primary | boolean default true | página primaria de la cuenta |
| password | string null | protección por contraseña (columna lista para Fase 2) |
| published_at | timestamp null | |
| timestamps, softDeletes | | |

Media asociada (medialibrary): colección `avatar` y colección `og` (imagen Open Graph; fallback = avatar).
Índices: `unique(user_id, slug)`, `index(user_id)`, `index(status)`.

### 4.3 `blocks` — tabla única con `type` + `data` JSON

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| public_id | ulid unique | usado en la URL de redirección de clics `/go/{public_id}` |
| page_id | bigint FK→pages | index, cascade |
| parent_id | bigint FK→blocks null | anidamiento futuro (grupos/contenedores) |
| type | string | `link`, `heading`, `text`, `image`, `embed`, `divider`, … |
| data | json | payload según el tipo (validado por DTO/registry) |
| position | unsignedInteger default 0 | orden (lista y flujo del grid) |
| size | enum('sm','md','lg') default 'md' | tamaño de card en grid; mapea a span de columnas/filas |
| grid_col_span | tinyint default 1 | colocación explícita en Bento (Fase 2; columna lista) |
| grid_row_span | tinyint default 1 | idem |
| is_visible | boolean default true | |
| starts_at | timestamp null | programación de visibilidad (Fase 2; columna lista) |
| ends_at | timestamp null | idem |
| timestamps | | |

Índices: `index(page_id, position)`, `index(type)`.

**Justificación (tabla única `type` + JSON) frente a alternativas:**

- **STI (Single Table Inheritance)** obliga a columnas por subtipo o "comodines" → ensucia el esquema con cada tipo nuevo.
- **Polimorfismo (una tabla por tipo)** es correcto relacionalmente pero cada tipo = nueva tabla + modelo + migración + joins, y complica el drag & drop (una lista ordenada que mezcla tipos).
- **Tabla única `type` + `data` JSON (elegido)**: un solo modelo `Block`, drag & drop trivial (ordenar por `position`), y **agregar un tipo = un DTO + un componente Vue, sin migración**. La integridad del payload se garantiza en la capa de aplicación con `spatie/laravel-data` (un DTO por tipo: `LinkBlockData`, `HeadingBlockData`, `EmbedBlockData`, …), validado en el FormRequest. MySQL 8 permite índices funcionales sobre claves concretas del JSON si hiciera falta.

### 4.4 `social_links`

Fila fija de iconos sociales en el header/footer, distinta del cuerpo de bloques.

| Columna | Tipo |
|---|---|
| id | bigint PK |
| page_id | bigint FK→pages (index, cascade) |
| platform | string (`instagram`, `x`, `tiktok`, `youtube`, `github`, `whatsapp`, `email`, …) |
| url | string |
| position | unsignedInteger default 0 |
| timestamps | |

### 4.5 `themes`

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK→users null | null = preset global del sistema |
| name / slug | string | slug con spatie/sluggable |
| is_preset | boolean default false | |
| settings | json | fondo (color/gradiente/imagen), estilo de botón, radios, color de texto, `font_family` |
| timestamps | | |

`pages.theme.presetId` referencia el preset base; `pages.theme.tokens` guarda overrides puntuales sin duplicar el tema.

### 4.6 Analíticas — `analytics_events` + `analytics_daily`

**`analytics_events`** (evento crudo, discriminado por `type`):

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| page_id | bigint FK→pages (index) | |
| block_id | bigint FK→blocks null (index) | null en `page_view` |
| type | enum('page_view','link_click') | extensible (`form_submit`, …) |
| ip_hash | char(64) null | SHA-256 de IP + salt diario (anonimizada, no reversible) |
| country_code | char(2) null | GeoIP (Fase 2; columna lista) |
| device_type | enum('mobile','desktop','tablet','bot') null | |
| browser / os | string null | parseado del User-Agent |
| referrer_host | string null | solo host, no query completa |
| utm | json null | source/medium/campaign (Fase 2; columna lista) |
| target_url | string null | snapshot del destino del clic |
| created_at | timestamp (index) | |

Índices: `index(page_id, type, created_at)`, `index(block_id)`.

**`analytics_daily`** (rollup para dashboards rápidos y retención):

| Columna | Tipo |
|---|---|
| id | bigint PK |
| page_id | bigint FK (index) |
| block_id | bigint FK null |
| type | enum('page_view','link_click') |
| date | date |
| count | unsignedInteger |
| `unique(page_id, block_id, type, date)` | |

### 4.7 Settings globales

`spatie/laravel-settings` con clases tipadas persistidas en tabla `settings`:

- `RegistrationSettings { bool $open }` — abre/cierra el registro público.
- `SiteSettings { string $name; ?string $logo }` — branding del sistema.

### 4.8 Tablas de infraestructura

`media` (medialibrary), `roles`/`permissions`/`model_has_roles` (permission), `jobs`/`failed_jobs` (colas), `sessions`, `cache`. Provistas por los paquetes / starter kit.

### 4.9 Relaciones (resumen)

```
User 1───N Page 1───N Block
                └──N SocialLink
User 1───N Theme (personales)   Theme (preset, user_id null)
Page 1───N AnalyticsEvent   Block 1───N AnalyticsEvent
```

---

## 5. Flujo de render público (SSR) vs panel (CSR)

### Página pública

1. `GET /{username}` → `Public\PublicPageController@show`.
2. Resuelve el `User` por `username` y su página primaria publicada (404 si no existe o está en borrador y el visitante no es el dueño/admin).
3. Despacha `RecordPageViewJob` a la cola (no cuenta vistas del dueño; no bloquea el render).
4. Pasa como props Inertia: perfil, bloques visibles ordenados, social links, tema (CSS vars ya resueltas) y `meta` (SEO/OG).
5. **Los meta/OG se renderizan server-side** en `resources/views/app.blade.php` leyendo `$page['props']['meta']` → los crawlers y los previews sociales los ven en el HTML inicial, sin necesitar el servidor Node de Inertia SSR.
6. `pages/public/Show.vue` (con `PublicLayout`) hidrata el cuerpo en el cliente:
   - Las **CSS variables del tema** se aplican inline en el root → **sin flash (FOUC)** de color.
   - Vite hace code-splitting por página: **el JS del editor (vuedraggable, etc.) NO se descarga en la página pública**.

> Mejora futura: activar el SSR completo de Inertia (`resources/js/ssr.ts` + `php artisan inertia:start-ssr`) para renderizar también el cuerpo server-side.

### Redirección de clics

`GET /go/{block:public_id}` → registra `link_click` (job en cola), guarda `target_url` snapshot y hace `302` al destino. Funciona sin JS y es fiable.

### Panel / Admin (CSR)

- `GET /dashboard`, `/pages/{page}/edit`, `/admin/*`.
- Layouts persistentes de Inertia: `AppLayout` (usuario), `AdminLayout` (gate `role:admin`), `GuestLayout` (auth).
- El editor edita un store Pinia y persiste vía endpoints PATCH que responden JSON (autosave), sin recargar toda la página Inertia.

---

## 6. Sistema de bloques extensible

El corazón de "features sin límite". Un contrato en el frontend, espejado en el backend.

### 6.1 Contrato de un tipo de bloque (frontend, TS)

```ts
// types/blocks.ts
export interface BlockDefinition<D = Record<string, unknown>> {
  type: string;                    // 'link', 'heading', 'embed'...
  label: string;                   // etiqueta en el menú "Agregar bloque"
  icon: Component;                 // ícono del menú
  category: 'basic' | 'media' | 'social';
  defaultData: () => D;            // datos iniciales al crear
  schema: ZodType<D>;              // validación en cliente
  renderer: Component;             // render público (list y grid-aware)
  editor: Component;               // formulario del panel
  layouts?: ('list' | 'grid')[];   // layouts soportados (default: ambos)
}
```

### 6.2 Registry con auto-registro

```
resources/js/blocks/link/index.ts        → exporta la BlockDefinition
resources/js/blocks/link/LinkBlock.vue    → renderer público
resources/js/blocks/link/LinkEditor.vue   → formulario del panel
```

```ts
// lib/blocks/registry.ts
const modules = import.meta.glob('../../blocks/*/index.ts', { eager: true });
export const blockRegistry: Record<string, BlockDefinition> =
  Object.values(modules).reduce((acc, m) => {
    const def = (m as any).default as BlockDefinition;
    acc[def.type] = def; return acc;
  }, {});
```

**Agregar un tipo nuevo = crear una carpeta con `index.ts` + 2 `.vue`.** Ni el menú "Agregar", ni el renderer, ni el editor tienen switches que tocar.

### 6.3 Espejo en el backend (integridad)

El JSON `data` se valida en el servidor por tipo:

- `app/Enums/BlockType.php` — enum de tipos.
- `app/Blocks/BlockTypeRegistry.php` — mapea `type` → clase de tipo.
- `app/Blocks/Types/LinkBlockType.php` (etc.) — expone `rules()` para el FormRequest y el DTO `App\Data\LinkBlockData`.

**Cómo agregar un bloque, en 3 pasos** (documentar y mantener):
1. Frontend: carpeta en `resources/js/blocks/<tipo>/` con `index.ts`, renderer y editor.
2. Backend: entrada en `BlockType` + clase `*BlockType` con `rules()` + DTO en `app/Data/`.
3. Tests: un test de feature que cree/renderice el bloque.

---

## 7. Layout: lista vs grid (Bento)

La página elige `layout: 'list' | 'grid'`. El **mismo catálogo de bloques** se renderiza distinto según el contenedor:

- `list` → `BlockList.vue` (columna flex, botones full-width).
- `grid` → `BentoGrid.vue` (CSS `grid-template-columns: repeat(N, 1fr)`; cada bloque toma `grid-column: span …` según `size`/spans).

Los renderers son **"layout-aware"**: el contenedor hace `provide('layout', layout)` y cada `*Block.vue` hace `inject('layout')` para ajustar su presentación (un Link es barra ancha en lista y card en grid). **Un componente, dos presentaciones** — no se duplican renderers. Si un bloque no soporta un layout (`layouts`), se degrada a span completo.

---

## 8. Sistema de temas

### Tokens (CSS variables)

```
--bio-bg, --bio-bg-image, --bio-fg,
--bio-btn-bg, --bio-btn-fg, --bio-btn-border,
--bio-btn-radius, --bio-btn-shadow, --bio-accent,
--bio-font, --bio-card-bg (Bento)
```

### Almacenamiento y aplicación

- Los presets viven en `lib/themes.ts` (frontend) con espejo en `config/themes.php` (para validar overrides server-side). Presets iniciales: *Clean Light*, *Midnight*, *Sunset gradient*, *Mono dark*.
- `pages.theme` guarda `{ presetId, mode, tokens }`.
- `lib/cssVars.ts`: `themeToCssVars(theme)` → `{ '--bio-bg': ... }`.
- **Público (SSR):** el controlador resuelve el tema y `PublicLayout` pinta `:style="cssVars"` en el root → sin FOUC. `mode: 'auto'` respeta `prefers-color-scheme`.
- **Editor:** el `ThemePanel.vue` edita `store.page.theme`; el preview aplica las mismas vars en un contenedor scopeado (`.bio-scope`) → cambio instantáneo.

---

## 9. Embeds y seguridad

**Principio: nunca `v-html` con contenido de terceros; construir iframes propios desde IDs validados.**

- **Flujo:** el usuario pega una URL → validación server-side contra una **allowlist** de dominios + regex que extrae `provider` + `id` → se guardan datos canónicos (`{ provider, id, extra }`), **no HTML crudo**.
- **YouTube:** `https://www.youtube-nocookie.com/embed/{id}`.
- **Spotify:** `https://open.spotify.com/embed/{type}/{id}`.
- **TikTok:** no permite iframe directo por ID → **oEmbed server-side** cacheado y sanitizado (HTMLPurifier con allowlist), o bien un **facade** (thumbnail + botón que enlaza a TikTok).
- **Endurecimiento del iframe:** `loading="lazy"`, `referrerpolicy="strict-origin-when-cross-origin"`, `sandbox="allow-scripts allow-same-origin allow-presentation"`, `allow="encrypted-media; picture-in-picture"`.
- **CSP:** `frame-src` restringido a los dominios permitidos (`spatie/laravel-csp`).
- **Facade click-to-load** para YouTube/Spotify: thumbnail liviano; el iframe se monta al hacer click (mejor performance, menos cookies de terceros).

El resolver vive en `app/Services/EmbedResolver.php`.

---

## 10. Analíticas

1. **Vistas de página (server-side, en SSR):** al renderizar la página pública se despacha `RecordPageViewJob`. Anonimización: `ip_hash = sha256(ip + salt_diario)`; deduplicación por `ip_hash + page_id` en una ventana (~30 min) vía caché para no inflar vistas. Se guardan device/browser/os, referrer host y UTM si vienen.
2. **Clics (redirect trackeable):** `/go/{block:public_id}` registra `link_click` (job en cola), guarda `target_url` snapshot y redirige. Funciona sin JS.
3. **Agregación:** comando programado `analytics:rollup` (Scheduler) consolida `analytics_events` → `analytics_daily`. El dashboard consulta los rollups (rápido).
4. **Escalabilidad (Fase 2 sin rehacer):** las columnas `country_code`, `device_type`, `referrer_host`, `utm` ya existen → CTR, geografía (GeoIP), dispositivos, referrers y series temporales salen directo de los datos. GA4/Meta Pixel se suman como IDs en `pages` + snippets en el SSR, sin tocar el modelo.

Privacidad: sin PII, IP hasheada; solo el dueño y el admin ven las métricas. Ver [`BUSINESS_RULES.md`](BUSINESS_RULES.md#8-analíticas-y-privacidad).

---

## 11. Autorización y roles

- **Roles:** `admin` y `user` (spatie/laravel-permission).
- **Policies:** `PagePolicy`, `BlockPolicy`, `SocialLinkPolicy`, `ThemePolicy`. La regla base: `page.user_id === user.id`. Bloques/social/temas autorizan a través de la página padre.
- **Bypass admin:** `Gate::before` devuelve `true` para el rol `admin`.
- **Área admin:** grupo de rutas `/admin/*` con middleware `role:admin` (gestión de usuarios: listar/activar/desactivar/asignar rol; settings globales; abrir/cerrar registro).
- **Registro cerrable:** middleware `EnsureRegistrationOpen` que consulta `RegistrationSettings::open` en la ruta de registro.
- **Sesión web** para el panel; **Sanctum** se reserva para la API pública (Fase 2).

Detalle de permisos en [`BUSINESS_RULES.md`](BUSINESS_RULES.md#1-roles-y-permisos).

---

## 12. Estructura de carpetas

### Backend

```
app/
  Actions/
    Pages/       CreatePage, UpdatePage, PublishPage
    Blocks/      CreateBlock, UpdateBlock, ReorderBlocks, DeleteBlock
    Analytics/   RecordPageView, RecordLinkClick, AggregateDailyStats
  Blocks/        BlockTypeRegistry + Types/*BlockType
  Data/          LinkBlockData, HeadingBlockData, TextBlockData, ImageBlockData, EmbedBlockData, PageData
  Enums/         Role, BlockType, PageLayout, BlockSize, SocialPlatform, EventType, PageStatus
  Http/
    Controllers/{Public,Dashboard,Admin}/
    Requests/    StorePageRequest, UpdatePageRequest, StoreBlockRequest, ReorderBlocksRequest, ...
    Middleware/  EnsureRegistrationOpen
  Jobs/          RecordPageViewJob, RecordLinkClickJob
  Models/        User, Page, Block, SocialLink, Theme, AnalyticsEvent, AnalyticsDaily
  Policies/      PagePolicy, BlockPolicy, SocialLinkPolicy, ThemePolicy
  Services/      QrService, AnalyticsService, EmbedResolver
  Settings/      RegistrationSettings, SiteSettings
database/{migrations,factories,seeders}/   # RoleSeeder, AdminUserSeeder, ThemePresetSeeder, DemoUserSeeder
routes/web.php
```

### Frontend

```
resources/js/
  app.ts  ssr.ts
  Pages/{Auth,Dashboard,Editor,Analytics,Settings,Admin,Public,Errors}/
  Layouts/       GuestLayout, AppLayout, AdminLayout, PublicLayout
  Components/{ui,editor,blocks,blocks-editor,public,common}/
  blocks/        link/ heading/ text/ image/ embed/ social/   (registry auto-cargado)
  composables/   useAutosave, useBlockDnd, useTheme, usePreview
  lib/           blocks/registry, themes, cssVars, oembed
  stores/        editor (Pinia)
  types/         models, blocks, theme, inertia.d.ts
```

### Rutas (referencia)

```
Público (SSR):   GET  /{username}                 PublicPageController@show
                 GET  /{username}/{slug}           (Fase 2, multi-página)
                 GET  /go/{block}                  LinkRedirectController
                 GET  /{username}/qr.svg           QrCodeController
Panel (auth):    GET  /dashboard
                 GET/PATCH  /pages/{page}          EditorController
                 POST/PATCH/DELETE  /pages/{page}/blocks[/{block}]
                 PATCH /pages/{page}/blocks/reorder
                 GET  /pages/{page}/analytics
Admin:           GET  /admin, /admin/users, /admin/settings
Slugs reservados: dashboard, admin, api, login, register, go, storage, build, ...
```

---

## 13. Testing

Pest, con foco en:

- **Unit** de tipos de bloque (validación del payload por tipo).
- **Feature** de rutas públicas (render, 404 en borrador, meta OG, `noindex`).
- **Feature** de autorización (un `user` no puede editar páginas ajenas; el `admin` sí accede).
- **Feature** de tracking (una vista/clic genera el evento; deduplicación de vistas).
- **Feature** de reordenamiento (drag & drop persiste `position` transaccionalmente).

---

## Roadmap por fases

### Fase 0 — Setup + documentación *(actual)*
Scaffolding del starter kit Vue (SSR on), `.env`/MySQL, Pint/Larastan/Pest. Paquetes core. Migraciones base + Enums. Seeders (roles, admin, temas preset, settings). Docs (`README`, `ARCHITECTURE`, `BUSINESS_RULES`).

### Fase 1 — MVP + contenido rico
Auth con registro cerrable. CRUD de páginas (perfil). Bloques `link`, `heading`, `text`, `image`, `embed` (YT/Spotify/TikTok) con drag & drop. Social links. Temas preset + overrides. Layout `list`/`grid`. Página pública SSR con OG/meta. QR. Analíticas (eventos + jobs + rollup + dashboard). Área admin. Policies + tests.

### Fase 2 — Avanzado
Personalización avanzada (fuentes, gradientes, thumbnails, links destacados, programación con `starts_at`/`ends_at`, redirect). Analíticas avanzadas (CTR, GeoIP, dispositivos, referrers, series temporales, GA4/Meta Pixel/UTM). Captura de email/newsletter + integraciones (Mailchimp, Zapier, webhooks) y **API con Sanctum**. Múltiples páginas por cuenta. i18n. Protección por contraseña. Age gate/NSFW. Exportación de datos.

### Fase 3 — Premium / monetización
Productos digitales, propinas, **Stripe/PayPal (Cashier)**. Planes de suscripción + feature gating (separado de roles). Dominio propio + SSL. Panel de facturación.
