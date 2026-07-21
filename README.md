# API REST de Tareas — Laravel + Sanctum

Instructivo de **uso e implementación** de la API REST desarrollada en **Laravel 12** con autenticación por tokens mediante **Laravel Sanctum**. Permite registrar/iniciar sesión de usuarios y gestionar un **CRUD de Tareas** protegido: solo usuarios autenticados pueden crear, ver, editar o eliminar tareas.

## URL base (API ya desplegada)

```
http://177.7.32.156/JDCBact4t4/api/
```

Todos los endpoints de este documento se arman agregando la ruta a esta URL base. Por ejemplo, el registro queda en:

```
http://177.7.32.156/JDCBact4t4/api/register
```

---

## Tabla de contenido

1. [Requisitos previos](#1-requisitos-previos)
2. [Autenticación con Sanctum](#2-autenticación-con-sanctum)
3. [Endpoints de la API](#3-endpoints-de-la-api)
4. [Cómo probar la API (Bruno)](#4-cómo-probar-la-api-bruno)
5. [Instalación local (opcional, para desarrollo)](#5-instalación-local-opcional-para-desarrollo)
6. [Consideraciones para producción](#6-consideraciones-para-producción)
7. [Estructura del proyecto](#7-estructura-del-proyecto)
8. [Capturas de pantalla](#8-capturas-de-pantalla)

---

## 1. Requisitos previos

Para **usar** la API ya desplegada solo necesitas un cliente HTTP:

- Bruno o Postman, o
- `curl` desde una terminal, o
- Cualquier aplicación/frontend que pueda hacer peticiones HTTP.

Para **modificar o volver a desplegar** el proyecto necesitas además:

- PHP 8.2 o superior
- Composer
- MySQL (o SQLite para desarrollo rápido)
- Acceso SSH al VPS

---

## 2. Autenticación con Sanctum

Esta API **no usa sesiones ni cookies**, usa **tokens Bearer**. El flujo es:

1. Te registras (`POST /register`) o inicias sesión (`POST /login`).
2. La API te devuelve un `access_token`.
3. Ese token se envía en **cada** petición protegida dentro del header:
   ```
   Authorization: Bearer {access_token}
   ```
4. Si el token falta, es inválido o fue revocado, la API responde `401 Unauthorized`.
5. `POST /logout` revoca el token actual.

> Sin este header, **ningún** endpoint de tareas funcionará — están protegidos con el middleware `auth:sanctum`.

---

## 3. Endpoints de la API

Recuerda que todas las rutas de abajo van después de `http://177.7.32.156/JDCBact4t4/api`.

### Públicos (no requieren token)

| Método | Endpoint    | Descripción                        |
|--------|-------------|--------------------------------------|
| POST   | `/register` | Registra un usuario y da un token   |
| POST   | `/login`    | Inicia sesión y da un token         |

### Protegidos (requieren header `Authorization: Bearer {token}`)

| Método | Endpoint       | Descripción                          |
|--------|----------------|----------------------------------------|
| GET    | `/me`          | Datos del usuario autenticado         |
| POST   | `/logout`      | Cierra sesión (revoca el token)       |
| GET    | `/tasks`       | Lista tareas (paginado)               |
| GET    | `/tasks/{id}`  | Muestra una tarea                     |
| POST   | `/tasks`       | Crea una tarea                        |
| PUT    | `/tasks/{id}`  | Actualiza una tarea (completa)        |
| PATCH  | `/tasks/{id}`  | Actualiza una tarea (parcial)         |
| DELETE | `/tasks/{id}`  | Elimina una tarea                     |

### Ejemplo — Registro

**Request**
```
POST http://177.7.32.156/JDCBact4t4/api/register
Content-Type: application/json
Accept: application/json

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

### Ejemplo — Login

**Request**
```
POST http://177.7.32.156/JDCBact4t4/api/login
Content-Type: application/json
Accept: application/json

{
  "email": "ana@example.com",
  "password": "password123"
}
```

### Ejemplo — Crear tarea

**Request**
```
POST http://177.7.32.156/JDCBact4t4/api/tasks
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json

{
  "autor": "Ana",
  "title": "Comprar pan",
  "description": "Ir a la panadería",
  "category": "hogar",
  "priority": "media"
}
```
`priority` acepta únicamente: `baja`, `media`, `alta`.

### Ejemplo — Listar tareas (paginado)

```
GET http://177.7.32.156/JDCBact4t4/api/tasks?per_page=10&page=1
Authorization: Bearer {token}
```

### Errores comunes

| Código | Cuándo ocurre                                                    |
|--------|---------------------------------------------------------------------|
| 401    | No enviaste el token, es inválido o fue revocado                    |
| 422    | Datos de entrada inválidos (falta un campo, formato incorrecto)     |
| 404    | El recurso o la ruta no existe                                       |

---

## 4. Cómo probar la API (Bruno)

Se incluye una colección de **Bruno** lista para importar (carpeta `bruno-collection/`), con:

- Registro, login, `/me`, logout
- Prueba de rechazo sin token (401)
- CRUD completo de tareas
- Prueba de validación fallida (422)

### Pasos

1. Abrir Bruno → **Open Collection** → seleccionar la carpeta `bruno-collection/`.
2. Editar el entorno **Local** (o crear uno nuevo, por ejemplo **VPS**) y cambiar la variable `baseUrl` a:
   ```
   http://177.7.32.156/JDCBact4t4/api
   ```
3. Seleccionar ese entorno en la esquina superior derecha de Bruno.
4. Correr las peticiones en orden: **Register → Login → Me → Tasks (Create/List/Show/Update/Delete) → Logout**.

El token se guarda automáticamente en una variable de entorno tras el login/registro (gracias al script `post-response` de cada petición), así no hay que copiarlo manualmente en cada llamada siguiente.

---

## 5. Instalación local (opcional, para desarrollo)

Si necesitas modificar el código y correrlo en tu máquina antes de subir cambios al VPS:

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

La API local queda en `http://127.0.0.1:8000/api/...`. Recuerda cambiar el `baseUrl` de Bruno de vuelta a esta URL mientras pruebas en local, y volver a apuntarlo al VPS cuando quieras validar el entorno real.

### Actualizar el VPS con cambios nuevos

```bash
cd /var/www/ruta-del-proyecto
git pull
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

---

## 6. Consideraciones para producción

- **Migrar a HTTPS cuanto antes**: actualmente la API corre en HTTP puro (`http://177.7.32.156/...`). El token viaja en el header `Authorization` sin cifrar, por lo que podría ser interceptado en la red. Si consigues un dominio, se puede activar HTTPS gratis con Let's Encrypt/Certbot.
- **`APP_DEBUG=false`** siempre en producción — nunca expongas trazas de error a usuarios finales.
- **Variables de entorno**: nunca subas el archivo `.env` a un repositorio público (verifica que esté en `.gitignore`).
- **Base de datos de producción**: usa MySQL (no SQLite) y un usuario de base de datos con permisos limitados, no `root`.
- **CORS**: si un frontend en otro dominio va a consumir esta API, configura `config/cors.php` con los orígenes permitidos.
- **Expiración de tokens** (opcional): en `config/sanctum.php`, define `expiration` en minutos para que los tokens no vivan indefinidamente.
- **Cachear configuración y rutas** en producción para mejorar el rendimiento:
  ```bash
  php artisan config:cache
  php artisan route:cache
  ```
- **Permisos de carpetas**: `storage/` y `bootstrap/cache/` deben tener permisos de escritura para el usuario del servidor web.
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

Evidencia de las pruebas realizadas contra la API ya desplegada en el VPS:

| Prueba | Captura |
|---------|---------|
| Registro | <img src="https://github.com/user-attachments/assets/af78c9c5-a6a6-4f71-ac1b-879cf0ba7d81" width="700"> |
| Login | <img src="https://github.com/user-attachments/assets/86f51a1e-8144-4e63-b8d4-90ca746a4b22" width="700"> |
| Sin token (401) | <img src="https://github.com/user-attachments/assets/7f745c7b-0594-437c-8c07-be47ffdd8ddb" width="700"> |
| Crear tarea | <img src="https://github.com/user-attachments/assets/38687987-29ef-4996-9d53-81275286bf81" width="700"> |
| Listar tareas (paginado) | <img src="https://github.com/user-attachments/assets/0dfcda08-f581-49f2-9c5e-bfb1b5108b6d" width="700"> |
| Actualizar tarea | <img src="https://github.com/user-attachments/assets/05a68944-51fb-433c-88e7-e48ca94f98d2" width="700"> |
| Eliminar tarea | <img src="https://github.com/user-attachments/assets/ab9e24ad-8b48-4704-808a-364c6d665e97" width="700"> |
| Validación fallida (422) | <img src="https://github.com/user-attachments/assets/075ce484-d414-4ce2-8713-3036a6766e0c" width="700"> |
