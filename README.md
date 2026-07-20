# Bioinfo

**Bioinfo** es una aplicación autohospedada tipo *link-in-bio* (Linktree / Beacons / Bento). Permite crear mini-páginas de perfil con enlaces, bloques de contenido y embeds, con analíticas propias y control total de los datos.

El objetivo del proyecto es tener un Linktree propio al que se le puedan **agregar features sin límite**, manteniendo **siempre la información bajo nuestro control** (autohospedado, base de datos propia).

> Estado: **Fase 0 — Planificación y documentación**. Todavía no hay código de aplicación; este repositorio arranca con la documentación de diseño (`README.md`, `ARCHITECTURE.md`, `BUSINESS_RULES.md`).

---

## Índice

- [Stack tecnológico](#stack-tecnológico)
- [Features (visión general)](#features-visión-general)
- [Requisitos](#requisitos)
- [Instalación en Laragon (Windows)](#instalación-en-laragon-windows)
- [Desarrollo](#desarrollo)
- [Comandos útiles](#comandos-útiles)
- [Build de producción](#build-de-producción)
- [Cuentas de prueba](#cuentas-de-prueba)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Roadmap por fases](#roadmap-por-fases)
- [Documentación relacionada](#documentación-relacionada)
- [Licencia](#licencia)

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje backend | PHP 8.4 |
| Framework backend | Laravel 12 |
| Puente front/back | Inertia.js 2 (monolito, sin API separada en el MVP) |
| Frontend | Vue 3 + TypeScript |
| SSR | Inertia SSR (Node) — prioritario para la página pública (SEO/Open Graph) |
| Estilos | Tailwind CSS v3.4 + componentes shadcn-vue (radix-vue), solo en el panel |
| Build | Vite |
| Base de datos | MySQL 8 |
| Colas | Database driver (MVP); escalable a Redis |
| Tests | Pest |

### Paquetes principales

| Paquete | Uso |
|---|---|
| `spatie/laravel-permission` | Roles `admin` / `user` y autorización |
| `spatie/laravel-medialibrary` | Avatares, imágenes de bloques, imágenes Open Graph, thumbnails |
| `spatie/laravel-data` | DTOs tipados del payload JSON de cada tipo de bloque |
| `spatie/laravel-sluggable` | Slugs de páginas y temas |
| `spatie/laravel-settings` | Settings globales del sistema (registro abierto/cerrado, nombre del sitio) |
| `endroid/qr-code` | Generación de QR (SVG/PNG) de la página pública |
| `pestphp/pest` | Tests unitarios y de feature |
| `laravel/pint` + `larastan/larastan` | Formato y análisis estático |

---

## Features (visión general)

El MVP ("MVP + contenido rico") incluye:

- Autenticación con roles `admin` y `user` (registro que el admin puede abrir/cerrar).
- Perfil público: avatar, título, bio.
- **Bloques ilimitados**: link/botón, encabezado (heading), texto, imagen y **embeds** (YouTube / Spotify / TikTok).
- **Editor con drag & drop** y **preview en vivo** (panel de edición + mock de móvil).
- Barra de **iconos de redes sociales**.
- **Temas básicos** (colores de fondo/botón/texto, modo claro/oscuro) vía CSS variables.
- **Layout configurable por página**: lista clásica (tipo Linktree) o grid de cards (tipo Bento).
- **Analíticas propias**: vistas de página y clics por link.
- **QR** de la página y opciones de compartir.
- **SEO / Open Graph** (meta tags, imagen de preview, `noindex` en borradores).
- **Área de administración** global para el rol `admin` (gestión de usuarios y settings del sistema).

El catálogo completo de features y su priorización está en [Roadmap por fases](#roadmap-por-fases) y en [`ARCHITECTURE.md`](ARCHITECTURE.md).

---

## Requisitos

- **Laragon** (Windows) con:
  - PHP **8.4**
  - MySQL 8
  - Composer 2
- **Node.js 20+** y npm
- Extensiones PHP habituales de Laravel: `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `gd` (o `imagick`) para el procesamiento de imágenes.

> El entorno de desarrollo objetivo es Laragon en `c:\laragon\www\bioinfo`, servido como `http://bioinfo.test`.

---

## Instalación en Laragon (Windows)

> Estos pasos aplican una vez que se haya scaffolding del proyecto con el starter kit oficial de Laravel 12 (Vue + Inertia). Ver [ARCHITECTURE.md](ARCHITECTURE.md#fase-0--scaffolding) para el detalle del scaffolding inicial.

1. **Ubicar el proyecto** en el docroot de Laragon:
   ```
   c:\laragon\www\bioinfo
   ```

2. **Instalar dependencias**:
   ```bash
   composer install
   npm install
   ```

3. **Variables de entorno**:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Base de datos**: crear la base `bioinfo` en MySQL (Laragon → Database) y configurar en `.env`:
   ```dotenv
   APP_URL=http://bioinfo.test

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bioinfo
   DB_USERNAME=root
   DB_PASSWORD=

   QUEUE_CONNECTION=database
   ```

5. **Migrar y sembrar** (roles, admin de prueba, temas preset):
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

6. **Virtual host**: Laragon crea automáticamente `http://bioinfo.test`. Si no, usar *Laragon → Reload* o `php artisan serve`.

---

## Desarrollo

En terminales separadas:

```bash
# 1) Vite (assets frontend con HMR)
npm run dev

# 2) (Opcional si no se usa el host de Laragon) servidor PHP
php artisan serve

# 3) Worker de colas (analytics, procesamiento de imágenes)
php artisan queue:work

# 4) SSR de Inertia (necesario para probar el render de la página pública)
php artisan inertia:start-ssr
```

Página pública de un perfil: `http://bioinfo.test/{username}`
Panel del usuario: `http://bioinfo.test/dashboard`
Área admin: `http://bioinfo.test/admin`

---

## Comandos útiles

```bash
php artisan migrate:fresh --seed   # recrear DB desde cero con datos de prueba
php artisan db:seed                # re-sembrar
php artisan analytics:rollup       # consolidar eventos crudos en agregados diarios
php artisan test                   # correr la suite Pest
./vendor/bin/pint                  # formatear código PHP
./vendor/bin/phpstan analyse       # análisis estático (larastan)
npm run build                      # build de producción (incluye bundle SSR)
```

---

## Build de producción

```bash
npm run build                 # compila cliente + bundle SSR
php artisan optimize          # cachea config, rutas, vistas
php artisan migrate --force
php artisan inertia:start-ssr # correr como servicio (supervisor / task)
php artisan queue:work        # correr como servicio
```

Programar el scheduler (para `analytics:rollup` y limpieza):

```
* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## Cuentas de prueba

Sembradas por `AdminUserSeeder` / `DemoUserSeeder` (ver `database/seeders`):

| Rol | Email | Contraseña |
|---|---|---|
| admin | `admin@bioinfo.test` | `password` |
| user | `demo@bioinfo.test` | `password` |

> Cambiar estas credenciales antes de cualquier despliegue.

---

## Estructura del proyecto

```
bioinfo/
├── app/
│   ├── Actions/            # lógica de negocio (una acción por caso de uso)
│   ├── Blocks/             # registry backend de tipos de bloque + validación
│   ├── Data/               # DTOs (spatie/laravel-data) de payloads de bloque
│   ├── Enums/              # Role, BlockType, PageLayout, BlockSize, EventType...
│   ├── Http/
│   │   ├── Controllers/{Public,Dashboard,Admin}/
│   │   ├── Requests/
│   │   └── Middleware/
│   ├── Jobs/               # RecordPageViewJob, RecordLinkClickJob...
│   ├── Models/             # User, Page, Block, SocialLink, Theme, AnalyticsEvent...
│   ├── Policies/           # PagePolicy, BlockPolicy...
│   ├── Services/           # QrService, EmbedResolver, AnalyticsService...
│   └── Settings/           # RegistrationSettings, SiteSettings
├── database/{migrations,factories,seeders}/
├── resources/js/
│   ├── Pages/              # páginas Inertia (Dashboard, Editor, Public, Admin...)
│   ├── Layouts/            # AppLayout, AdminLayout, PublicLayout, GuestLayout
│   ├── Components/{ui,editor,blocks,blocks-editor,public,common}/
│   ├── blocks/             # definiciones de tipos de bloque (registry auto-cargado)
│   ├── composables/  lib/  stores/  types/
│   └── app.ts  ssr.ts
├── routes/web.php
├── README.md
├── ARCHITECTURE.md
└── BUSINESS_RULES.md
```

Detalle completo en [ARCHITECTURE.md](ARCHITECTURE.md).

---

## Roadmap por fases

- **Fase 0 — Setup + documentación** *(actual)*: scaffolding del starter kit, paquetes core, migraciones base, seeders, docs.
- **Fase 1 — MVP + contenido rico**: auth con roles, perfil, bloques (link/heading/texto/imagen/embed), editor drag & drop, iconos sociales, temas, layout lista/grid, analíticas de vistas y clics, QR, SEO/OG, área admin.
- **Fase 2 — Avanzado**: personalización avanzada (fuentes, gradientes, thumbnails, links destacados, programación/redirect), analíticas avanzadas (CTR, geografía, dispositivos, referrers, GA/Meta Pixel/UTM), captura de email + integraciones, API con Sanctum, múltiples páginas por cuenta, i18n, protección por contraseña, age gate/NSFW, exportación de datos.
- **Fase 3 — Premium / monetización**: productos digitales, propinas, Stripe/PayPal, planes de suscripción + feature gating, dominio propio + SSL.

Detalle en [ARCHITECTURE.md](ARCHITECTURE.md#roadmap-por-fases).

---

## Documentación relacionada

- [`ARCHITECTURE.md`](ARCHITECTURE.md) — decisiones técnicas, modelo de datos, sistema de bloques extensible, flujos de render.
- [`BUSINESS_RULES.md`](BUSINESS_RULES.md) — reglas de negocio: roles, unicidad de username, visibilidad de bloques, privacidad de analytics, etc.

---

## Licencia

Por definir (proyecto personal / autohospedado).
