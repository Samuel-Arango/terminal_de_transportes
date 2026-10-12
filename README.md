# Terminal de Transportes

Sistema web en PHP + MySQL (PDO) para gestionar un terminal de transporte:
rutas, empresas, vehículos, horarios, cotización, **compra y venta de tiquetes**
y **validación de abordaje en puerta**.

---

## Requisitos

- PHP 8.0 o superior (extensiones `pdo_mysql`, `openssl`)
- MySQL 5.7+ / MariaDB 10.4+ (XAMPP, WAMP, Laragon, etc.)
- Apache (o el servidor embebido de PHP para desarrollo)

---

## Estructura del proyecto

```
terminal_de_transportes-main/
├── app/                     Código de la aplicación (páginas)
│   ├── includes/
│   │   ├── config.php       Carga de configuración (.env / entorno)
│   │   ├── db.php           Conexión PDO
│   │   ├── auth.php         Sesiones, CSRF, roles, utilidades
│   │   ├── tickets.php      Lógica de venta/emisión de tiquetes
│   │   ├── header.php       Cabecera y navegación
│   │   └── footer.php
│   ├── index.php            Inicio
│   ├── login.php / logout.php
│   ├── dashboard.php        Panel según rol
│   ├── rutas.php / cotizar.php
│   ├── comprar.php          Compra en línea (cliente)
│   ├── venta.php            Taquilla (admin / empresa)
│   ├── tiquetes.php         Mis tiquetes / ventas
│   ├── ver_tiquete.php      Comprobante con código QR
│   ├── validar.php          Validación en puerta (admin / empresa)
│   ├── empresas.php / vehiculos.php / horarios.php
│   └── .env.example         Plantilla de configuración
├── css/estilo.css           Estilos (branding)
├── images/logo1.png         Logotipo
└── Database/
    ├── shema.sql            Instalación limpia (esquema v3)
    ├── migracion_v2.sql     Migración v1 -> v2
    └── migracion_v3.sql     Migración v2 -> v3
```

---

## Instalación

### 1. Copiar el proyecto

Coloca la carpeta dentro del directorio web de tu servidor, por ejemplo:

```
C:\xampp\htdocs\terminal_de_transportes-main
```

### 2. Crear la base de datos

**Opción A — Instalación nueva (recomendado).**
Abre http://localhost/phpmyadmin y en la pestaña **Importar** carga:

```
Database/shema.sql
```

O por línea de comandos:

```bash
mysql -u root < Database/shema.sql
```

Esto crea la base `terminal` con datos de ejemplo.

**Opción B — Ya tenías la versión anterior.**
Importa primero `Database/migracion_v2.sql` y luego `Database/migracion_v3.sql`
sobre tu base existente (ajusta el nombre de la base dentro del archivo).

### 3. Configurar credenciales

Copia `app/.env.example` a `app/.env` y ajusta los valores:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=terminal
DB_USER=root
DB_PASS=
```

> El archivo `.env` nunca debe subirse al repositorio (ya está en `.gitignore`).
> Si no existe `.env`, se usan estos mismos valores por defecto.

### 4. Listo

Abre en el navegador:

```
http://localhost/terminal_de_transportes-main/app/
```

---

## Usuarios de prueba

| Rol           | Correo                   | Contraseña |
| ------------- | ------------------------ | ---------- |
| Administrador | admin@terminal.com       | 123456     |
| Empresa       | empresa@terminal.com     | 123456     |
| Cliente       | cliente@terminal.com     | 123456     |

> Cambia estas contraseñas en producción.

---

## Flujos principales

- **Cliente:** Inicio -> Cotizar (elige ruta y horario) -> Comprar -> Ver tiquete con QR.
- **Taquilla (admin/empresa):** Taquilla -> selecciona horario y cantidad -> emite tiquetes.
- **Puerta (admin/empresa):** Validar -> escanea el QR o escribe el código. El tiquete pasa
  a estado `usado`; si se escanea dos veces avisa que ya fue validado.

---

## Seguridad implementada

- Contraseñas con `password_hash()` / `password_verify()` (bcrypt) y re-hash automático.
- Credenciales fuera del código, en variables de entorno / `.env`.
- Consultas preparadas (PDO) con `ATTR_EMULATE_PREPARES = false`.
- Protección **CSRF** en todos los formularios; borrados por `POST`.
- Sesiones seguras: `HttpOnly`, `SameSite`, `Secure` (en HTTPS), regeneración de ID
  y cierre por inactividad (30 min).
- Control de acceso por rol y verificación de propiedad de los tiquetes/compras.
- Escapado de salida (`htmlspecialchars`) para prevenir XSS.
- Venta de tiquetes con transacción y bloqueo (`FOR UPDATE`) para evitar sobreventa.
- `.htaccess` que bloquea el acceso a `.env`, `includes/` y `Database/`.

---

## Notas

- El código QR de los tiquetes se genera con el servicio `quickchart.io`, por lo que
  requiere conexión a internet para mostrarse.
- El escáner de cámara de `validar.php` usa la librería `html5-qrcode` (CDN) y
  la cámara del dispositivo; el ingreso manual del código siempre está disponible.
