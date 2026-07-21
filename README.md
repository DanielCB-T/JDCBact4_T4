# API REST de Tareas — Laravel + Sanctum

API REST desarrollada en **Laravel 12** con autenticación por tokens mediante **Laravel Sanctum**. Permite registrar/iniciar sesión de usuarios y gestionar un **CRUD de Tareas** protegido: solo usuarios autenticados pueden crear, ver, editar o eliminar tareas.

---

## Tabla de contenido

1. [Requisitos previos](#1-requisitos-previos)
2. [Instalación local](#2-instalación-local)
3. [Autenticación con Sanctum](#3-autenticación-con-sanctum)
4. [Endpoints de la API](#4-endpoints-de-la-api)
5. [Cómo probar la API](#5-cómo-probar-la-api)
6. [Consideraciones para implementarla en producción](#6-consideraciones-para-implementarla-en-producción)
7. [Estructura del proyecto](#7-estructura-del-proyecto)
8. [Capturas de pantalla](#8-capturas-de-pantalla)

---

## 1. Requisitos previos

Antes de correr o desplegar esta API, asegúrate de tener:

- PHP 8.2 o superior
- Composer
- MySQL (o SQLite para desarrollo rápido)
- Extensiones de PHP: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`
- Un cliente para probar peticiones HTTP (Bruno o Postman)

---

## 2. Instalación local

```bash
git clone <url-de-tu-repositorio>
cd api-laravel

composer install
cp .env.example .env
php artisan key:generate

# Configura DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD en .env

php artisan migrate

php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000`.

---

## 3. Autenticación con Sanctum

Esta API **no usa sesiones ni cookies**, usa **tokens Bearer**. El flujo es:

1. Te registras (`POST /api/register`) o inicias sesión (`POST /api/login`).
2. La API te devuelve un `access_token`.
3. Ese token se envía en **cada** petición protegida dentro del header:
   ```
   Authorization: Bearer {access_token}
   ```
4. Si el token falta, es inválido o fue revocado, la API responde `401 Unauthorized`.
5. `POST /api/logout` revoca el token actual.

> ⚠️ Sin este header, **ningún** endpoint de tareas funcionará — están protegidos con el middleware `auth:sanctum`.

---

## 4. Endpoints de la API

### Públicos (no requieren token)

| Método | Endpoint         | Descripción                        |
|--------|------------------|--------------------------------------|
| POST   | `/api/register`  | Registra un usuario y da un token   |
| POST   | `/api/login`     | Inicia sesión y da un token         |

### Protegidos (requieren header `Authorization: Bearer {token}`)

| Método | Endpoint            | Descripción                          |
|--------|---------------------|----------------------------------------|
| GET    | `/api/me`           | Datos del usuario autenticado         |
| POST   | `/api/logout`       | Cierra sesión (revoca el token)       |
| GET    | `/api/tasks`        | Lista tareas (paginado)               |
| GET    | `/api/tasks/{id}`   | Muestra una tarea                     |
| POST   | `/api/tasks`        | Crea una tarea                        |
| PUT    | `/api/tasks/{id}`   | Actualiza una tarea (completa)        |
| PATCH  | `/api/tasks/{id}`   | Actualiza una tarea (parcial)         |
| DELETE | `/api/tasks/{id}`   | Elimina una tarea                     |

### Ejemplo — Registro

**Request**
```json
POST /api/register
{
  "name": "Ana Perez",
  "email": "ana@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response `201`**
```json
{
  "message": "Usuario registrado correctamente.",
  "user": { "id": 1, "name": "Ana Perez", "email": "ana@example.com" },
  "access_token": "1|xxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

### Ejemplo — Crear tarea

**Request**
```json
POST /api/tasks
Authorization: Bearer {token}

{
  "autor": "Ana",
  "title": "Comprar pan",
  "description": "Ir a la panadería",
  "category": "hogar",
  "priority": "media"
}
```
`priority` acepta únicamente: `baja`, `media`, `alta`.

### Errores comunes

| Código | Cuándo ocurre                                             |
|--------|-------------------------------------------------------------|
| 401    | No enviaste el token, es inválido o fue revocado            |
| 422    | Datos de entrada inválidos (falta un campo, formato incorrecto) |
| 404    | El recurso o la ruta no existe                               |

---

## 5. Cómo probar la API

Se incluye una colección de **Bruno** lista para importar (carpeta `bruno-collection/`), con:

- Registro, login, `/me`, logout
- Prueba de rechazo sin token (401)
- CRUD completo de tareas
- Prueba de validación fallida (422)

Pasos:
1. `php artisan serve`
2. Abrir Bruno → **Open Collection** → seleccionar `bruno-collection/`
3. Elegir el entorno **Local**
4. Correr las peticiones en orden (Register → Login → Me → Tasks → Logout)

El token se guarda automáticamente en una variable de entorno tras el login/registro, así no hay que copiarlo manualmente en cada petición.

> _Espacio para captura: colección de Bruno abierta con las carpetas Auth y Tasks_
>
> ![Colección de Bruno](./docs/images/bruno-collection.png)

> _Espacio para captura: respuesta exitosa del login mostrando el access_token_
>
> ![Login exitoso](./docs/images/login-response.png)

> _Espacio para captura: intento de acceso sin token mostrando el 401_
>
> ![Rechazo sin token](./docs/images/401-sin-token.png)

---

## 6. Consideraciones para implementarla en producción

- **`APP_DEBUG=false`** siempre en producción — nunca expongas trazas de error a usuarios finales.
- **HTTPS obligatorio**: el token viaja en el header `Authorization`; sin HTTPS puede ser interceptado.
- **Variables de entorno**: nunca subas el archivo `.env` a un repositorio público (verifica que esté en `.gitignore`).
- **Base de datos de producción**: usa MySQL (no SQLite) y un usuario de base de datos con permisos limitados, no `root`.
- **CORS**: si un frontend en otro dominio va a consumir esta API, configura `config/cors.php` con los orígenes permitidos.
- **Expiración de tokens** (opcional): en `config/sanctum.php`, define `expiration` en minutos para que los tokens no vivan indefinidamente.
- **Cachear configuración y rutas** en producción para mejorar el rendimiento:
  ```bash
  php artisan config:cache
  php artisan route:cache
  ```
- **Permisos de carpetas**: `storage/` y `bootstrap/cache/` deben tener permisos de escritura para el usuario del servidor web (por ejemplo `www-data`).
- **Migraciones**: al desplegar cambios nuevos, corre `php artisan migrate --force` (el `--force` es necesario porque `APP_ENV=production` pide confirmación).

---

## 7. Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   └── TaskController.php
│   ├── Requests/Api/
│   │   ├── RegisterRequest.php
│   │   ├── LoginRequest.php
│   │   ├── StoreTaskRequest.php
│   │   └── UpdateTaskRequest.php
│   └── Resources/
│       ├── UserResource.php
│       └── TaskResource.php
└── Models/
    ├── User.php
    └── Task.php

routes/api.php
bootstrap/app.php
bruno-collection/
```

---

## 8. Capturas de pantalla

> Agrega aquí las evidencias de tus pruebas (Bruno o Postman). Sugerencia de contenido mínimo:
> - Registro exitoso (201)
> - Login exitoso (200) y login fallido (401)
> - Endpoint protegido rechazado sin token (401)
> - Crear, listar, ver, actualizar y eliminar una tarea
> - Un caso de validación fallida (422)

| Prueba                          | Captura |
|----------------------------------|---------|
| Registro                         | ![Registro](<img width="1627" height="427" alt="image" src="https://github.com/user-attachments/assets/af78c9c5-a6a6-4f71-ac1b-879cf0ba7d81" />
) |
| Login                             | ![Login](./docs/images/login.png) |
| Sin token (401)                   | ![Sin token](./docs/images/no-token.png) |
| Crear tarea                       | ![Crear tarea](./docs/images/create-task.png) |
| Listar tareas (paginado)          | ![Listar tareas](./docs/images/list-tasks.png) |
| Actualizar tarea                  | ![Actualizar tarea](./docs/images/update-task.png) |
| Eliminar tarea                    | ![Eliminar tarea](./docs/images/delete-task.png) |
| Validación fallida (422)          | ![Validación 422](./docs/images/validation-error.png) |
