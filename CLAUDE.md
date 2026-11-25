# Flourish Framework — PHP 8.5.13

Guía de trabajo del repositorio `scopweb/flourish-lib`.

**Versión del servidor de producción: PHP 8.5.13.** Está fijada en:

- `composer.json` → `"require": {"php": ">=8.5.13"}` y
  `"config": {"platform": {"php": "8.5.13"}}`
- `.php-version` → `8.5.13`
- `.github/workflows/tests.yml` → matriz de CI con 8.5.13 (referencia) y 8.4

La suite se verifica en **PHP 8.5.13** (referencia) y en **PHP 8.4.11**.

## Origen del proyecto

Flourish es un "unframework" de Will Bond (<http://flourishlib.com>), licencia MIT.
Este repositorio es la versión modernizada que mantiene `scopweb`.

- `upstream` = `imarc/flourish-classes` (el original, con las clases en la raíz del repo).
- `origin` = `scopweb/flourish-lib` (este repo).

## Estructura

| Directorio | Contenido |
|---|---|
| `lib/` | Las 71 clases de Flourish (`fCore`, `fDatabase`, `fORM`, …) |
| `enums/` | Enums de PHP 8.1+: `DatabaseType`, `MimeType`, `ImageFormat`, `EmailEncoding`, `MongoDbType`, `JsonToken` |
| `querybuilder/` | Constructor de consultas con interfaz fluida: `fQueryBuilder`, `fDB`, `fRawExpression` |
| `container/` | Contenedor de inyección de dependencias (PSR-11) |
| `providers/` | Service providers que conectan el contenedor con Flourish |
| `interfaces/` | Interfaces de los servicios (`DatabaseInterface`, `CacheInterface`, …) |
| `test/` | Suite de tests (ver abajo) |
| `documentacion/` | Referencia de API en HTML |

Las clases **no usan namespaces**: todos los nombres están en el espacio global.

## Cómo ejecutar los tests

```bash
php test/run_all_tests.php            # PHP 8.4
C:\temp\php85\php.exe test/run_all_tests.php   # PHP 8.5 (portable de pruebas)
```

Cada fichero de test se ejecuta en un **proceso aislado** (`test/run_single.php`),
de modo que un error fatal en un test no oculta el resultado de los demás.

El runner **falla si se emite una sola deprecación, warning o notice** durante la
ejecución: es la garantía de que la librería es limpia en PHP 8.5.

Ficheros de test principales:

- `test/php85_compatibility_test.php` — auditoría estática de las deprecaciones de
  PHP 8.5, carga de todas las clases, pruebas funcionales y **base de datos SQLite
  real** a través de `fDatabase` y del query builder.
- `test/fschema_backticks_test.php` — compatibilidad de `fSchema` con los
  identificadores de MySQL con acentos graves (`` ` ``) y comillas dobles.
- `test/deprecated_attributes_test.php` — política de `#[\Deprecated]`: ninguna API
  obsoleta puede ser llamada por la propia librería.

## Migración a PHP 8.5

El contrato de la migración está en [`MIGRATION_PHP85.md`](MIGRATION_PHP85.md).
Resumen de lo que se corrigió:

- **Casts no canónicos** `(boolean)`, `(integer)` → `(bool)`, `(int)` (deprecados en 8.5).
- **`utf8_decode()`/`utf8_encode()`** → `mb_strlen()` o `lengthWithoutMbString()`
  (deprecados en 8.2; `fUTF8` ya no depende de mbstring).
- **`__sleep()`/`__wakeup()`** → `__serialize()`/`__unserialize()`, manteniendo los
  antiguos por compatibilidad.
- **`curl_close()`** y **`imagedestroy()`** eliminados (deprecados en 8.5).
- **`PDO::MYSQL_ATTR_DIRECT_QUERY`** → `Pdo\Mysql::ATTR_DIRECT_QUERY`.
- **API `sqlite2`** (`sqlite_open`, `sqlite_query`, `sqlite_libencoding`…) eliminada:
  no existe desde PHP 5.4/7.
- **Extensión `mysql`** eliminada: los drivers son MySQLi y PDO.
- **Parámetros implícitamente nulables** (`$x = null` con tipo) → `?type $x = null`.
- **`E_STRICT`** eliminado del `switch` de `fCore` (deprecado en 8.4).
- **`get_magic_quotes_gpc()`** eliminado (no existe desde PHP 8.0).
- **`#[\ReturnTypeWillChange]`** en los métodos `ArrayAccess`/`Countable`/`Iterator`.
- **`fException` es abstracta**: nunca se instancia; se usan las subclases concretas.
- **`self` en callables** → `self::class . '::metodo'` (deprecado en 8.5).

### Recuperación de `lib/`

En el commit inicial de este repositorio **70 de las 71 clases de `lib/` estaban
vacías** (0 bytes); sólo `fSchema.php` tenía contenido. El código modernizado a
PHP 8.4 se recuperó del clon local `C:\MCPs\clone\flourish-classes`
(commit `fb82c93` "Restore PHP 8.4 migrated classes from working commit") y se
fusionó en la rama `feature/php-8.5`. Sobre esa versión se volvió a aplicar el
fix de backticks de `fSchema` (PR #21 de imarc/flourish-classes).

**Antes de dar por buena cualquier afirmación sobre el estado de la librería,
comprueba que los ficheros tienen contenido** y que la suite de tests pasa.

## Base de datos

`fDatabase` detecta el driver automáticamente:

| Tipo | Drivers soportados |
|---|---|
| `mysql` | `mysqli`, `pdo_mysql` |
| `postgresql` | `pgsql`, `pdo_pgsql` |
| `sqlite` | `pdo_sqlite` |
| `oracle` | `oci8`, `pdo_oci` |
| `mssql` | `sqlsrv` |

Las extensiones `mysql` (eliminada en PHP 7.0) y `sqlite` v2 (eliminada en
PHP 5.4/7) ya no se soportan: el código que las usaba se eliminó.

### Placeholders

`%s` string, `%i` integer, `%f` float, `%b` boolean, `%d` date, `%t` time,
`%p` timestamp, `%l` blob, `%r` identificador.

```php
$result = $db->query('SELECT * FROM users WHERE status = %s AND age > %i', 'active', 18);
```

### Transacciones

```php
$db->query('BEGIN');
$db->query('COMMIT');
$db->query('ROLLBACK');
```

## Query builder

```php
fDB::setDatabase($db);
$users = fDB::table('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.active', '=', 1)
    ->orderBy('users.name')
    ->get()
    ->fetchAllRows();
```

## Reglas de contribución

1. **Cero deprecaciones.** El runner falla si aparece una. Usa `php -l` y el test
   del área afectada antes de dar el trabajo por terminado.
2. **Cero cambios de API pública.** Los nombres de los métodos, sus firmas y los
   mensajes de excepción son el contrato con las aplicaciones que usan la librería.
3. **`fException` es abstracta**: usa siempre la subclase concreta que corresponda.
4. Si añades una API obsoleta, márcala con `#[\Deprecated]` y con `@deprecated` en
   el PHPDoc, y no la llames desde la propia librería.
5. No introduzcas dependencias externas: la librería no usa Composer más allá del
   autoload.
6. Toda clase nueva necesita su test en `test/` (terminación `_test.php`).
