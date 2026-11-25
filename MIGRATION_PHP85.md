# Migración a PHP 8.5 — reglas y hallazgos

Este documento es el contrato de la migración de Flourish a PHP 8.5.
Todas las correcciones deben respetar estas reglas: **cero cambios de
comportamiento**, solo eliminación de avisos deprecados.

## Contexto

- Objetivo: PHP 8.5 en producción. Se mantiene compatibilidad con PHP 8.4.
- En PHP 8.5 `E_DEPRECATED` ya no es silencioso en producción: cualquier
  aviso deprecado debe tratarse como un fallo del runner de tests.
- El runner (`test/run_all_tests.php`) ejecuta cada fichero de test en un
  proceso aislado y falla si se emite **cualquier** deprecación, warning o notice.

## Reglas de migración (PHP 8.2 → 8.5)

### R1. Casts no canónicos (deprecados en 8.5)
`(boolean)`, `(integer)`, `(double)`, `(binary)` → `(bool)`, `(int)`, `(float)`, `(string)`.
Afecta solo a casts reales, nunca a texto de documentación (`@return (integer) {…}`).

### R2. `utf8_decode()` / `utf8_encode()` (deprecados en 8.2)
Todos los usos del código son **conteos de caracteres**, no conversiones de
codificación: `strlen(utf8_decode($s))` → `mb_strlen($s, 'UTF-8')`.
No usar `mb_convert_encoding()`: eso cambiaría el resultado.

### R3. `__sleep()` / `__wakeup()` (soft-deprecated en 8.5)
Añadir `__serialize(): array` y `__unserialize(array $data): void` que
repliquen exactamente el comportamiento anterior, y **mantener** `__sleep()` /
`__wakeup()` delegando en ellos para no romper payloads serializados
anteriores ni consumidores que los invoquen explícitamente.

### R4. `curl_close()` (deprecado en 8.5)
Eliminar la llamada. `CurlHandle` se libera solo. No sustituir por nada.

### R5. `imagedestroy()` (deprecado en 8.5)
Eliminar la llamada. `GdImage` se libera solo. No sustituir por nada.

### R6. Constantes de driver de PDO (deprecadas en 8.5)
`PDO::MYSQL_ATTR_DIRECT_QUERY` → `Pdo\Mysql::ATTR_DIRECT_QUERY`
`PDO::sqliteCreateFunction()` → `Pdo\Sqlite::createFunction()`
Usar la constante/clase nueva si existe, con fallback a la antigua para
compatibilidad con PHP < 8.4 (`class_exists` / `defined`).

### R7. API `sqlite2` eliminada de PHP
`sqlite_libencoding()`, `sqlite_create_function()`, `sqlite_open()` y compañía
no existen desde PHP 5.4/7. Cualquier llamada a esas funciones es un error
fatal en PHP 8. El único driver de SQLite soportado es `pdo_sqlite`.

### R8. Excepciones concretas
`fException` es **abstracta**: instanciarla es un error fatal.
Usar la subclase concreta que corresponda (`fUnexpectedException`,
`fConnectivityException`, `fProgrammerException`, …).

### R9. No tocar
- Nombres de clases, métodos públicos ni firmas: son el contrato público.
- Formato de los mensajes de excepción existentes.
- La lógica de negocio.

## Verificación obligatoria

Después de cada cambio:

```
php -l <fichero>                                   # sin errores de sintaxis
php test/run_single.php test/<fichero>_test.php   # tests del área
```

Y al final, desde la raíz:

```
php test/run_all_tests.php
```

El runner falla si queda **una sola** deprecación.
