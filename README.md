# 🤖 Asistente de Tareas UNEMI - Bot de Telegram

![Estado](https://img.shields.io/badge/Desarrollo-Completado-success?style=for-the-badge&logo=checkmarx)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Supabase-336791?style=for-the-badge&logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Container-2496ED?style=for-the-badge&logo=docker&logoColor=white)

Sistema automatizado para la gestión y notificación de tareas universitarias, diseñado para ayudar a estudiantes de la UNEMI a mantener sus entregas al día mediante notificaciones inteligentes y comandos interactivos en Telegram.

## 🚀 Características Principales

- **Notificaciones Automáticas**: Envío diario de tareas que vencen en los próximos 7 días mediante Cron-Jobs.
- **Asistente Interactivo (Webhook)**: El bot responde en tiempo real a comandos:
  - `/hoy`: Muestra lo que vence en el día actual.
  - `/semana`: Resumen de las entregas de los próximos 7 días.
  - `/tareas`: Listado completo de pendientes.
  - `/ayuda`: Guía de comandos.
- **Gestión por Materias**: Clasificación visual de tareas con iconos de libros y colores.
- **Cálculo de Tiempos**: Indicador exacto de días restantes para cada entrega.

## 🛠️ Stack Tecnológico

- **Lenguaje:** ![PHP](https://img.shields.io/badge/-PHP-777BB4?style=flat-square&logo=php&logoColor=white) 8.4
- **Base de Datos:** ![PostgreSQL](https://img.shields.io/badge/-PostgreSQL-336791?style=flat-square&logo=postgresql&logoColor=white) (Alojada en **Supabase**)
- **Servidor Web:** ![Apache](https://img.shields.io/badge/-Apache-D22128?style=flat-square&logo=apache&logoColor=white)
- **Infraestructura:** ![Docker](https://img.shields.io/badge/-Docker-2496ED?style=flat-square&logo=docker&logoColor=white)
- **Despliegue:** ![Render](https://img.shields.io/badge/-Render-46E3B7?style=flat-square&logo=render&logoColor=white)
- **Automatización:** ![CronJob](https://img.shields.io/badge/-Cron--Job.org-000000?style=flat-square)

## 📂 Estructura del Proyecto

```text
sistema_tareas/
├── config/             # Configuración de conexión PDO
├── public/             # Punto de entrada público (Apache)
│   ├── index.php       # Formulario de registro de tareas
│   ├── notificador.php # Script para avisos automáticos
│   └── webhook.php     # Manejador de comandos de Telegram
├── scratch/            # Scripts de utilidad y migración
├── Dockerfile          # Configuración del contenedor
└── docker-compose.yml  # Orquestación local
```

## ⚙️ Configuración del Entorno (.env)

El proyecto utiliza variables de entorno para máxima seguridad:
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`: Credenciales de PostgreSQL.
- `TELEGRAM_TOKEN`: Token proporcionado por @BotFather.
- `TELEGRAM_CHAT_ID`: ID del chat donde se recibirán las notificaciones.
- `CRON_TOKEN`: Clave de seguridad para ejecutar el notificador externamente.

---
Desarrollado con ❤️ para optimizar el tiempo de estudio.
