# Reglas de negocio — Bioinfo

Reglas funcionales y de negocio del sistema. Es la fuente de verdad de "qué está permitido y qué no". Cuando una regla se implemente, debe cubrirse con un test (ver [`ARCHITECTURE.md`](ARCHITECTURE.md#13-testing)).

Cada regla lleva un identificador (`BR-x.y`) para poder referenciarla desde código y tests.

---

## Índice

1. [Roles y permisos](#1-roles-y-permisos)
2. [Cuentas y username](#2-cuentas-y-username)
3. [Páginas](#3-páginas)
4. [Bloques](#4-bloques)
5. [Visibilidad y programación](#5-visibilidad-y-programación)
6. [Layout y temas](#6-layout-y-temas)
7. [Embeds](#7-embeds)
8. [Analíticas y privacidad](#8-analíticas-y-privacidad)
9. [QR y compartir](#9-qr-y-compartir)
10. [SEO / Open Graph](#10-seo--open-graph)
11. [Moderación y límites de abuso](#11-moderación-y-límites-de-abuso)

---

## 1. Roles y permisos

Existen dos roles: **`admin`** y **`user`**.

- **BR-1.1** — Un `user` solo puede ver, crear, editar y borrar **sus propias** páginas, bloques, social links y temas personales.
- **BR-1.2** — El `admin` tiene acceso global: gestión de usuarios y settings del sistema. Puede **moderar** (desactivar/ocultar) contenido de cualquier usuario.
- **BR-1.3** — Por defecto, el `admin` **no edita el contenido creativo** de páginas ajenas; su intervención sobre contenido de terceros se limita a moderación (ocultar, despublicar, suspender). Toda acción de moderación queda registrada.
- **BR-1.4** — El área de administración (`/admin/*`) solo es accesible con rol `admin`. Cualquier otro acceso devuelve 403.
- **BR-1.5** — Un usuario no puede auto-asignarse el rol `admin`. Solo un `admin` asigna o revoca roles.
- **BR-1.6** — Debe existir siempre al menos un `admin` activo; el sistema impide desactivar o degradar al último admin.

---

## 2. Cuentas y username

- **BR-2.1** — El `username` es el handle público de la cuenta y forma la URL `/{username}`.
- **BR-2.2** — Formato válido: `^[a-z0-9_.-]{3,30}$` (minúsculas, dígitos, `_`, `.`, `-`; de 3 a 30 caracteres).
- **BR-2.3** — El `username` es **único de forma case-insensitive** (`Pablo` y `pablo` colisionan).
- **BR-2.4** — Existe una **lista de slugs reservados** que no pueden usarse como username: `dashboard`, `admin`, `api`, `login`, `register`, `logout`, `go`, `storage`, `build`, `qr`, `settings`, `password`, entre otros. La lista se mantiene en configuración.
- **BR-2.5** — Cambiar el `username` cambia la URL pública. (Fase 2: opción de conservar redirecciones desde el username anterior durante un período.)
- **BR-2.6** — El registro puede estar **abierto o cerrado** globalmente (`RegistrationSettings::open`, controlado por el admin). Con registro cerrado, la ruta de registro devuelve 403 / redirige a login.
- **BR-2.7** — El email es único y debe verificarse (según configuración del starter kit) antes de publicar una página.
- **BR-2.8** — Un `admin` puede **desactivar** una cuenta (`is_active = false`): el usuario no puede iniciar sesión y sus páginas dejan de servirse públicamente.
- **BR-2.9** — Al **borrar** una cuenta, sus páginas y bloques se eliminan (cascade) y el `username` queda liberado. Las analíticas agregadas pueden conservarse anonimizadas o purgarse según la política de retención.

---

## 3. Páginas

- **BR-3.1** — Cada usuario tiene **una página primaria** (`is_primary = true`), servida en `/{username}`. En el MVP es la única página por cuenta.
- **BR-3.2** — (Fase 2) Un usuario podrá tener múltiples páginas; las secundarias se sirven en `/{username}/{slug}` y el `slug` es **único por usuario** (`unique(user_id, slug)`).
- **BR-3.3** — Una página tiene estado `draft` (borrador) o `published` (publicada).
- **BR-3.4** — Una página en `draft` **solo es visible para su dueño y para un admin** (con indicación de "vista previa"); a cualquier otro visitante se le responde 404.
- **BR-3.5** — Solo una página **publicada** genera QR, es indexable y registra analíticas de vistas públicas.
- **BR-3.6** — Publicar una página requiere, como mínimo, `title` o al menos un bloque visible (evitar páginas vacías públicas).

---

## 4. Bloques

- **BR-4.1** — Los bloques son **ilimitados** en cantidad por página (sin tope en el MVP; ver límites de abuso en [§11](#11-moderación-y-límites-de-abuso)).
- **BR-4.2** — Cada bloque tiene un `type` de un conjunto conocido (`link`, `heading`, `text`, `image`, `embed`, `divider`, …). Un `type` desconocido se rechaza en validación.
- **BR-4.3** — El payload `data` de cada bloque se **valida según su tipo** (DTO + reglas del `BlockTypeRegistry`). Campos obligatorios por tipo:
  - `link`: `label` (texto), `url` (URL válida, esquema http/https).
  - `heading`: `text`.
  - `text`: `text` (con límite de longitud, ver BR-4.5).
  - `image`: imagen subida válida (ver BR-4.6) y `alt` recomendado.
  - `embed`: `provider` permitido + `id`/URL válidos (ver [§7](#7-embeds)).
- **BR-4.4** — El **orden** de los bloques lo determina `position`. El reordenamiento (drag & drop) se persiste transaccionalmente; el backend recalcula `position` a partir del array de IDs recibido y **no confía** en índices arbitrarios del cliente.
- **BR-4.5** — Límites de contenido (configurables): longitud máxima de `text` (p. ej. 5.000 caracteres), longitud de `label`/`title` (p. ej. 120), longitud de URLs.
- **BR-4.6** — Subida de imágenes: formatos permitidos (`jpg`, `png`, `webp`, `gif`), peso máximo (p. ej. 5 MB) y dimensiones máximas; se generan conversiones/thumbnails (medialibrary).
- **BR-4.7** — Todas las URLs de destino se normalizan y validan; se rechazan esquemas peligrosos (`javascript:`, `data:` no permitidos donde no corresponde).

---

## 5. Visibilidad y programación

- **BR-5.1** — Un bloque con `is_visible = false` **no se muestra al público**, pero sí es visible en el editor/preview del dueño.
- **BR-5.2** — (Fase 2) Programación: un bloque con `starts_at` y/o `ends_at` solo se muestra al público dentro de esa ventana temporal.
- **BR-5.3** — La ventana de programación se evalúa en la **zona horaria** definida a nivel de sistema o de usuario (a definir en Fase 2); se documenta y se es consistente.
- **BR-5.4** — El **preview del dueño** muestra todos los bloques (incluidos ocultos y fuera de ventana) claramente marcados como no públicos, para poder editarlos.
- **BR-5.5** — Un bloque oculto o fuera de ventana **no registra clics** públicos.

---

## 6. Layout y temas

- **BR-6.1** — Cada página define su `layout`: `list` (lista clásica) o `grid` (Bento). El mismo conjunto de bloques se renderiza según el layout.
- **BR-6.2** — Los temas se eligen de un conjunto de **presets del sistema**; el usuario puede aplicar **overrides** (colores, fuente, radios) sobre un preset.
- **BR-6.3** — Los overrides de tema se validan contra un esquema conocido de tokens; valores fuera de esquema se ignoran o rechazan (no se permite CSS arbitrario en el MVP).
- **BR-6.4** — (Recomendado / opcional) Validación de **contraste** de color para accesibilidad: advertir cuando la combinación fondo/texto no alcanza un ratio mínimo.
- **BR-6.5** — El modo de color puede ser `light`, `dark` o `auto` (respeta `prefers-color-scheme`).

---

## 7. Embeds

- **BR-7.1** — Solo se permiten proveedores de una **allowlist**: en el MVP, YouTube, Spotify y TikTok.
- **BR-7.2** — Se acepta la URL del proveedor y se **extrae y almacena el identificador canónico** (`provider` + `id`), nunca HTML crudo del usuario.
- **BR-7.3** — URLs que no correspondan a un proveedor permitido, o de las que no se pueda extraer un `id` válido, se **rechazan** con un mensaje claro.
- **BR-7.4** — Los iframes se construyen del lado del sistema con dominios de embed oficiales, `sandbox` y `referrerpolicy` restrictivos, y una CSP que limita `frame-src`.
- **BR-7.5** — Para TikTok (sin iframe directo) se usa oEmbed cacheado y sanitizado, o un facade (thumbnail + enlace). Si el oEmbed falla, el bloque muestra un estado de error / facade en lugar de romper la página.

---

## 8. Analíticas y privacidad

- **BR-8.1** — Se registran dos tipos de evento en el MVP: `page_view` (vista de página) y `link_click` (clic en link).
- **BR-8.2** — **No se almacena PII.** La IP se guarda solo como hash irreversible (`sha256(ip + salt_diario)`), usado para deduplicar vistas, no para identificar.
- **BR-8.3** — Las vistas se **deduplican** por `ip_hash + page_id` dentro de una ventana temporal (~30 min) para no inflar el conteo.
- **BR-8.4** — Las métricas de una página **solo son visibles para su dueño y para un admin**. Ningún visitante público ve estadísticas.
- **BR-8.5** — Los clics se registran mediante la ruta de redirección `/go/{block}`, guardando un snapshot del `target_url` (para que el histórico sea correcto aunque el bloque luego cambie o se borre).
- **BR-8.6** — Los bloques ocultos o fuera de ventana no generan eventos públicos (ver BR-5.5).
- **BR-8.7** — Política de retención: los eventos crudos pueden purgarse/particionarse tras consolidarse en los agregados diarios; los agregados se conservan más tiempo. Los valores concretos se definen en configuración.
- **BR-8.8** — (Fase 2) Respeto de señales de privacidad (p. ej. Do-Not-Track) y aviso de cookies cuando se integren pixels de terceros (GA4/Meta).

---

## 9. QR y compartir

- **BR-9.1** — El QR de una página codifica su **URL pública** (`/{username}`), y solo está disponible para páginas **publicadas** (BR-3.5).
- **BR-9.2** — El QR se puede descargar en SVG/PNG y puede adoptar el color del tema de la página.
- **BR-9.3** — Compartir ofrece: copiar URL, descargar QR, Web Share API nativa (móvil) e intents a redes.

---

## 10. SEO / Open Graph

- **BR-10.1** — Cada página publicada expone `<title>`, `meta description` y etiquetas Open Graph / Twitter Card.
- **BR-10.2** — Defaults: `meta_title` → `title` de la página → nombre del usuario; `og:image` → imagen OG de la página → avatar → imagen por defecto del sistema.
- **BR-10.3** — Las páginas en `draft` se sirven con `noindex` (no deben aparecer en buscadores).
- **BR-10.4** — Las rutas del panel y del admin nunca se indexan.

---

## 11. Moderación y límites de abuso

- **BR-11.1** — Rate limiting en creación de cuentas, páginas y bloques, y en subida de imágenes, para mitigar abuso.
- **BR-11.2** — Contenido prohibido (según política del sitio) puede ser ocultado/despublicado por un admin; la acción queda registrada.
- **BR-11.3** — Un admin puede **suspender** una cuenta: sus páginas dejan de servirse públicamente hasta que se levante la suspensión.
- **BR-11.4** — Al borrar o suspender una cuenta, su `username`/URL deja de resolver públicamente (404 o página de estado), evitando que otro usuario "herede" tráfico indebidamente hasta que el username se libere formalmente.
- **BR-11.5** — Los límites concretos (cantidades, tamaños, ventanas de rate limit) viven en configuración para poder ajustarse sin cambiar código.
