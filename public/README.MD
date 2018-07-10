Instalación:
	Importar pictures.sql en una base de datos mysql.
	Configurar base de datos en la función "getDB" del script public/index.php
	En una consola posicionarse en el directorio "public" y ejecutar el comando:
		php -S localhost:8888 -t . index.php
	Ejecutará en la sigueinte URL: http://localhost:8888 la API REST.




API REST

Usuario:
	debe especificarse el ID de usuario en el header
	X-HTTP-USER-ID


GET http://localhost:8888/pictures
	Devuelve listado de cuadros.

	Filtros disponibles:
	filters[country]
	filters[painter]

	Ej. País:
	http://localhost:8888/pictures?filters[country]=italia

	Ej. Pintor:
	http://localhost:8888/pictures?filters[country]=ITALIA&filters[painter]=leonardo da vince

	Campos disponibles que se devolverán:
	id_picture
	picture_name
	id_painter
	id_country
	country
	painter_name

	Pueden especificarse cuáles debe devolverse.

	Ej:
	http://localhost:8888/pictures?fields=picture_name,id_country



GET http://localhost:8888/pictures/{id}
	Devuelve cuadro específico.




PUT http://localhost:8888/pictures/update/{id}
	Actualiza cuadro ID 1.

	Ej. Campos:
	name = nombre 2
	id_painter = 2
	id_country = 2


PUT http://localhost:8888/pictures/add
	Añade nuevo cuadro.

	Campos admitidos:
	name
	id_painter
	id_countr