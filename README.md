# MDF 301 Redirects

Plugin de WordPress para gestionar redirecciones 301 de forma simple, con soporte de reglas por URL y wildcard `*`.

## Descripción

**MDF 301 Redirects** permite crear redirecciones permanentes (HTTP 301) desde URLs antiguas hacia nuevas URLs, sin necesidad de editar archivos del servidor.

Está pensado para usuarios que necesitan una herramienta clara y rápida desde el panel de administración.

## Funcionalidades

- Activación/desactivación global del plugin.
- Reglas de redirección por URL de origen y destino.
- Soporte de wildcard `*` para redirigir todo el sitio.
- Exclusión de rutas (por ejemplo `wp-admin`, `wp-login.php` y rutas personalizadas).
- Interfaz en el administrador de WordPress con tabla editable.
- Almacenamiento en tabla propia de base de datos.

## Estructura del plugin

- `mdf-redirect-301.php`: archivo principal, hooks y carga de clases.
- `includes/class-mdf-db.php`: creación de tabla y operaciones de guardado/lectura.
- `includes/class-mdf-redirector.php`: lógica de redirección 301 en frontend.
- `includes/class-mdf-admin.php`: menú y panel de configuración.
- `assets/admin.css`: estilos del panel.
- `assets/admin.js`: agregar/quitar reglas dinámicamente.
- `uninstall.php`: limpieza al desinstalar.

## Instalación

1. Copiar la carpeta `mdf-redirect-301` dentro de `wp-content/plugins/`.
2. Ir al panel de WordPress > **Plugins**.
3. Activar **MDF 301 Redirects**.
4. Ir al menú lateral **MDF Redirects** para configurar reglas.

## Cómo usar

### 1) Estado global

En la sección **Estado global** puedes habilitar o deshabilitar todas las redirecciones del plugin.

### 2) Rutas excluidas

En **Rutas excluidas**, agrega una ruta por línea para impedir redirecciones en esas URLs.

Valores por defecto:

- `wp-admin`
- `wp-login.php`

También puedes agregar rutas personalizadas, por ejemplo:

- `mi-admin-personalizado`
- `acceso-interno`

### 3) Reglas de redirección

Cada regla tiene:

- **Origen**: ruta o URL de origen.
- **Destino**: URL final a la que se redirige.
- **Activa**: permite activar/desactivar una regla individual.

Después de editar, presiona **Guardar cambios**.

## Wildcard `*`

Si en **Origen** colocas `*`, esa regla redirige todas las URLs del sitio (excepto las rutas excluidas) al destino indicado.

Este tipo de regla se evalúa primero.

Ejemplo:

- Origen: `*`
- Destino: `https://example.com/nuevo-destino`

Resultado: cualquier URL del sitio (salvo excluidas) se redirige 301 a ese destino.

## Ejemplos de uso

### Cambio de URL de una página

- Origen: `/servicios-viejo`
- Destino: `https://tusitio.com/servicios`

### Contenido eliminado con reemplazo

- Origen: `/promo-2023`
- Destino: `https://tusitio.com/promociones`

### Migración de secciones completas

Usar wildcard para enviar temporalmente todo el tráfico a una URL consolidada mientras se reorganiza el sitio.

## Qué es una redirección 301

Una redirección 301 indica que una URL se movió de forma permanente.

Se usa habitualmente para:

- mantener la experiencia de usuario al cambiar enlaces,
- evitar errores 404,
- consolidar URLs antiguas hacia nuevas rutas.

## Comportamiento técnico

- El plugin usa el hook `template_redirect`.
- Si el plugin está desactivado globalmente, no aplica redirecciones.
- Se omiten rutas excluidas antes de evaluar reglas.
- Si existe una regla wildcard activa, se aplica antes que las reglas específicas.

## Base de datos

Tabla creada al activar el plugin:

- `{prefix}mdf_redirects`

Columnas:

- `id`
- `source`
- `destination`
- `is_active`
- `created_at`

Además se guardan opciones en `wp_options`:

- `mdf_redirects_enabled`
- `mdf_excluded_paths`

## Desinstalación

Al desinstalar el plugin se elimina:

- la tabla de reglas (`{prefix}mdf_redirects`),
- las opciones `mdf_redirects_enabled` y `mdf_excluded_paths`.

## Autor

Plugin realizado por **José Marin de la Fuente**  
Sitio web: [https://www.marindelafuente.com.ar](https://www.marindelafuente.com.ar)
