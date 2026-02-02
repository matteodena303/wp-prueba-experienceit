# Prueba técnica WordPress – Listado de usuarios con AJAX

Prueba técnica desarrollada en WordPress que muestra un listado de usuarios paginado, con formulario de búsqueda y carga dinámica mediante AJAX, simulando una llamada POST a un API externo.

## Requisitos

- Docker
- Docker Compose

## Instalación y ejecución

1. Clonar el repositorio:

```bash
git clone https://github.com/matteodena303/wp-prueba-experienceit.git
cd wp-prueba-experienceit
```

2. Levantar el entorno con Docker:

```bash
docker compose up -d --build
```

3. Importar la base de datos incluida (si no se ha importado aún):

```bash
docker exec -i wp_prueba_db mysql -uroot -proot wordpress < BBDD/db.sql
```

4. Abrir en el navegador:
   http://localhost:8090

5. Ver el listado de usuarios:

En el panel de WordPress ya existe una página de prueba con el shortcode:

```
[ult_user_list]
```

Si quieres añadirlo manualmente, crea una nueva página y pega el shortcode.

## Acceso al panel de administración

- Usuario: **admin**
- Password: **Admin-prueba303**

Los usuarios no se almacenan en la base de datos.
La respuesta del API se simula en el backend respetando la estructura indicada en el enunciado y se obtiene mediante una llamada POST.

## Base de datos

Se incluye un export de la base de datos en la carpeta /BBDD/db.sql para facilitar la verificación del proyecto.
