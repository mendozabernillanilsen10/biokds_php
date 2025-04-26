# 📘 Proyecto PHP Nativo con MySQL

Este proyecto fue desarrollado utilizando **PHP nativo** (sin frameworks) y una base de datos **MySQL**. Permite realizar operaciones CRUD y manejar información de manera eficiente en el backend.

## ⚙️ Tecnologías usadas

- 🐘 PHP nativo
- 🐬 MySQL
- 💡 HTML/CSS (opcional si tiene interfaz)
- 🔄 XAMPP / LAMP / WAMP (servidor local recomendado)

## 🧩 Funcionalidades principales

- Conexión a base de datos MySQL
- Consultas SQL seguras usando `mysqli` o `PDO`
- Ejecución de sentencias `SELECT`, `INSERT`, `UPDATE`, `DELETE`
- Validaciones básicas
- Código estructurado y reutilizable

## 📁 Estructura del proyecto

📦 tu-proyecto/ ├── 📄 index.php ├── 📄 db.php ├── 📄 funciones.php ├── 📂 sql/ │ └── schema.sql └── 📄 README.md

bash
Copiar
Editar

## 🔌 Configuración inicial

1. Clona este repositorio:

2. Crea una base de datos en MySQL y ejecuta el script:


sql
Copiar
Editar
SOURCE sql/schema.sql;
Configura tu conexión en db.php:

php
Copiar
Editar
$conn = new mysqli("localhost", "usuario", "contraseña", "nombre_bd");
Levanta el proyecto con un servidor local como XAMPP o similar.

🛡️ Recomendaciones
Usa sentencias preparadas (prepared statements) para evitar SQL Injection.

Valida siempre los datos desde el cliente y el servidor.

Realiza respaldos periódicos de tu base de datos.

📮 Contacto
Si tienes dudas o sugerencias, puedes contactarme en [tu correo o LinkedIn].

Proyecto desarrollado por [Tu Nombre] 🚀

yaml
Copiar
Editar

---
