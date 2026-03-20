# Laravel Invite Manager API

API REST desarrollada en Laravel para gestionar:

- Usuarios
- Organizaciones
- Relacion usuario-organizacion con rol
- Flujo de invitaciones con token y expiracion

Roles soportados por organizacion:

- `member`
- `admin`
- `manager`

Reglas de permisos implementadas:

- `admin` y `manager` pueden invitar usuarios
- Solo `admin` puede eliminar miembros
- `member` tiene acceso de lectura limitado a su organizacion

## Estructura Recomendada y Motivo

Se utilizo estructura convencional de Laravel (Controllers + Requests + Resources + Policies + Models) porque:

- Reduce tiempo de implementacion para una prueba de 4-6 horas
- Maximiza legibilidad para evaluacion tecnica
- Aprovecha convenciones nativas de Laravel 13
- Mantiene el flujo de invitaciones claro sin sobreingenieria

Capas principales usadas:

- `app/Http/Controllers/Api`: endpoints y orquestacion del flujo
- `app/Http/Requests`: validacion y autorizacion de entrada
- `app/Http/Resources`: salida JSON consistente
- `app/Policies`: permisos por rol y organizacion
- `app/Models`: relaciones y reglas de dominio
- `app/Mail`: correo de invitacion
- `database/migrations`: modelo relacional

## Puesta en Marcha (WSL2 + Sail)

### 1. Clonar o usar proyecto generado con Sail

```bash
curl -s "https://laravel.build/laravel-invite-manager" | bash
cd laravel-invite-manager
```

### 2. Levantar contenedores

```bash
./vendor/bin/sail up -d
```

### 3. Configurar entorno

```bash
cp .env.example .env
./vendor/bin/sail artisan key:generate
```

Configurar Mailtrap en `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=invitations@example.com
MAIL_FROM_NAME="Laravel Invite Manager"
```

### 4. Migrar base de datos

```bash
./vendor/bin/sail artisan migrate:fresh
```

### 5. Ejecutar tests

```bash
./vendor/bin/sail artisan test
```

### 6. Cargar datos demo (opcional)

```bash
./vendor/bin/sail artisan db:seed
```

Usuarios demo creados por seeders:

- `admin@example.com` / `password123`
- `manager@example.com` / `password123`
- `member@example.com` / `password123`

Nota: tus credenciales de Mailtrap ya estan configuradas en tu `.env` local.

## Endpoints

Base URL: `http://localhost/api/v1`

### Auth

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me` (auth:sanctum)
- `POST /auth/logout` (auth:sanctum)

### Organizations

- `GET /organizations` (auth:sanctum)
- `POST /organizations` (auth:sanctum)
- `GET /organizations/{organization}` (auth:sanctum)
- `PUT /organizations/{organization}` (auth:sanctum)
- `DELETE /organizations/{organization}` (auth:sanctum, admin)
- `GET /organizations/{organization}/members` (auth:sanctum)
- `DELETE /organizations/{organization}/members/{user}` (auth:sanctum, solo admin)

### Invitations

- `POST /organizations/{organization}/invitations` (auth:sanctum, admin o manager)
- `GET /invitations/{token}` (public)
- `POST /invitations/accept` (public)

## Flujo de Invitaciones

1. Usuario autenticado con rol `admin` o `manager` crea invitacion para email + rol destino.
2. Si ya existia invitacion pendiente para el mismo email en la misma organizacion, se revoca y se crea una nueva.
3. Se envia email via Mailtrap con token de invitacion.
4. El receptor consulta invitacion por token (`GET /invitations/{token}`).
5. El receptor acepta invitacion (`POST /invitations/accept`).
6. Si el email no existe en plataforma, se crea usuario en ese paso.
7. Si el email ya existe, se asigna membresia directamente.
8. Se devuelve token de autenticacion Sanctum y acceso a la organizacion.

## Decisiones de Dominio Implementadas

- API versionada en `/api/v1`
- Autenticacion con Sanctum
- Expiracion de invitacion: 7 dias
- Reinvitar mismo email + organizacion: revoca invitacion pendiente anterior
- Aceptacion desde token sin login previo
- Errores con formato estandar de Laravel

## Capturas Mailtrap

Agregar en la entrega:

- Captura de lista de emails recibidos en Mailtrap
- Captura del detalle del email de invitacion con token

Seccion sugerida para adjuntar:

- `docs/captures/mailtrap-inbox.png`
- `docs/captures/mailtrap-invitation-detail.png`

## Postman

Coleccion incluida para importar en Postman:

- `docs/postman/Laravel-Invite-Manager.postman_collection.json`

Variables importantes de la coleccion:

- `base_url`: por defecto `http://localhost/api/v1`
- `auth_token`: se rellena automaticamente tras login/register
- `organization_id`: se rellena desde listado de organizaciones (o manual)
- `invitation_token`: se rellena tras crear invitacion

## Uso de IA en la Prueba

Se utilizo IA para:

- Proponer estructura de carpetas y capas optimizada para tiempo de prueba
- Generar esqueletos iniciales de controladores/requests/resources/policies
- Revisar consistencia del flujo de invitaciones y permisos
- Acelerar redaccion de README y bitacora de cambios

Validaciones manuales realizadas:

- Ajuste de reglas de autorizacion por rol (`admin`, `manager`, `member`)
- Correcciones sobre flujos de autenticacion y aceptacion de invitacion
- Ejecucion de migraciones y tests automatizados
- Verificacion de respuestas HTTP y estructura JSON

## Bitacora de Implementacion

La bitacora cronologica de comandos y pasos ejecutados se encuentra en:

- `IMPLEMENTATION_LOG.md`
