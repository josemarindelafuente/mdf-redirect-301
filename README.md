# MDF 301 Redirects

![MDF 301 Redirects Banner](assets/images/banner.jpg)

Plugin de WordPress para gestionar redirecciones 301 con una interfaz simple, soporte de wildcard `*`, controles de seguridad y configuración guiada por pestañas.

## Descripción

**MDF 301 Redirects** permite crear redirecciones permanentes (HTTP 301) desde URLs antiguas hacia nuevas URLs sin editar `.htaccess` ni configuración del servidor.

Está diseñado para usuarios que buscan una herramienta fácil de usar, pero con validaciones de seguridad para evitar errores comunes y configuraciones peligrosas.

## Funcionalidades principales

- Activación/desactivación global del plugin.
- Reglas de redirección por origen y destino.
- Soporte de wildcard `*` para redirigir todo el sitio.
- Exclusión de rutas sensibles (`wp-admin`, `wp-login.php` y personalizadas).
- Dominios externos permitidos (lista blanca) para redirecciones fuera del sitio.
- Validación de reglas para evitar configuraciones inválidas.
- Prevención de ciclos de redirección entre reglas.
- Panel de administración organizado por pestañas.

## Panel de administración (tabs)

El panel de **MDF Redirects** está dividido en 4 pestañas:

1. **Configuración**
   - Estado global del plugin.
   - Rutas excluidas.
   - Dominios externos permitidos.

2. **Reglas de redirección**
   - Tabla editable de reglas.
   - Activación/desactivación por regla.
   - Agregar/eliminar filas dinámicamente.

3. **Cómo funciona**
   - Explicación breve de uso paso a paso.

4. **Por qué usar 301**
   - Motivos prácticos y casos reales de cuándo conviene usar redirecciones 301.

## Estructura del plugin

- `mdf-redirect-301.php`: archivo principal, hooks, carga de clases y defaults.
- `includes/class-mdf-db.php`: creación de tabla, sanitización y persistencia.
- `includes/class-mdf-redirector.php`: lógica de redirección en frontend.
- `includes/class-mdf-admin.php`: menú, tabs, formularios y validaciones.
- `assets/admin.css`: estilos del panel.
- `assets/admin.js`: agregar/quitar reglas dinámicamente.
- `uninstall.php`: limpieza al desinstalar.

## Instalación

1. Copiar la carpeta `mdf-redirect-301` dentro de `wp-content/plugins/`.
2. Ir a **Plugins** en WordPress.
3. Activar **MDF 301 Redirects**.
4. Ir al menú lateral **MDF Redirects**.

## Uso rápido

### 1) Configuración

- Activa el plugin en **Estado global**.
- Define rutas excluidas (una por línea).
- Si vas a redirigir a otro dominio, agrégalo en **Dominios externos permitidos**.

### 2) Reglas

En la pestaña **Reglas de redirección** crea tus reglas:

- **Origen**: ruta de entrada o `*`.
- **Destino**: URL completa o ruta interna.
- **Activa**: habilita/deshabilita la regla sin borrarla.

Guarda con **Guardar reglas**.

## Wildcard `*`

Si en **Origen** colocas `*`, esa regla redirige todas las URLs del sitio (excepto las rutas excluidas) al destino indicado.

Ejemplo:

- Origen: `*`
- Destino: `https://www.marindelafuente.com.ar`

Nota: para destinos externos, ese dominio debe estar en **Dominios externos permitidos**.

## Seguridad y validaciones implementadas

- Verificación de nonce y capacidad (`manage_options`) al guardar.
- Sanitización estricta de origen, destino y rutas excluidas.
- Política de hosts permitidos para destinos externos (allowlist).
- Soporte de URLs internas y normalización de formatos de destino.
- Uso de `wp_safe_redirect` para redirecciones.
- Detección de ciclos entre reglas activas.
- Restricción de reglas wildcard activas simultáneas.
- Límites de cantidad/tamaño de reglas para mayor robustez.

## Comportamiento técnico

- Hook principal: `template_redirect`.
- Si el plugin está desactivado globalmente, no redirige.
- Se excluyen rutas sensibles antes de evaluar reglas.
- La regla wildcard se evalúa antes que reglas específicas.
- No aplica redirección en contexto admin, AJAX o REST.

## Base de datos

Tabla creada al activar:

- `{prefix}mdf_redirects`

Columnas:

- `id`
- `source`
- `destination`
- `is_active`
- `created_at`

Opciones guardadas en `wp_options`:

- `mdf_redirects_enabled`
- `mdf_excluded_paths`
- `mdf_allowed_destination_hosts`

## Desinstalación

Al desinstalar se eliminan:

- la tabla de reglas (`{prefix}mdf_redirects`),
- `mdf_redirects_enabled`,
- `mdf_excluded_paths`,
- `mdf_allowed_destination_hosts`.

## Autor

Plugin realizado por **José Marin de la Fuente**  
Sitio web: [https://www.marindelafuente.com.ar](https://www.marindelafuente.com.ar)

## Repositorio

Detalles del proyecto: [https://github.com/josemarindelafuente/mdf-redirect-301](https://github.com/josemarindelafuente/mdf-redirect-301)
