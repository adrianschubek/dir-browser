

<div align="center">

<!-- # Directory Browser
_Easiest way to browse your files and folders on the web._
![](dir-browser.png) -->

[![](https://user-images.githubusercontent.com/19362349/235141708-34db874f-729c-4e50-b458-a3c0cb5d6c07.png)](https://dir.adriansoftware.de)

<!-- <img src="https://user-images.githubusercontent.com/19362349/235141708-34db874f-729c-4e50-b458-a3c0cb5d6c07.png" alt="" style="
    /* overflow: hidden; */
    object-fit: scale-down;
    width: 100%;
"> -->

<!--
![](p1.png)
![](p2.png)
-->
</div>



<h2 align="center">

  Visita [dir.adriansoftware.de](https://dir.adriansoftware.de) para ver la documentación y más! 

</h2>

<!-- ![image](https://github.com/adrianschubek/dir-browser/assets/19362349/102e058f-7d9e-457f-bde5-d61a8b0733f7) -->

<!-- <img src="https://github.com/adrianschubek/dir-browser/assets/19362349/102e058f-7d9e-457f-bde5-d61a8b0733f7" alt="" style="
    /* overflow: hidden; */
    object-fit: scale-down;
    width: 100%;
"> -->


## Demostración

https://dir-demo.adriansoftware.de

## Características
- **Contador de descargas** para todos los archivos
- Seguro por defecto. Acceso de **solo lectura**
- Servicio de archivos **extremadamente rápido** mediante **nginx**
- Soporte para renderizado de markdown de **README**
- **API JSON** para acceso programático
- **Descarga en lote** de archivos y carpetas en un archivo zip
- Verificación de **integridad de archivos** con **hashes**
- **Descripciones personalizadas** y **etiquetas** para archivos y carpetas
- **Búsqueda** y **ordenamiento** integrados
- Protección con **contraseña**
- **Ocultar** archivos y carpetas
- Modo claro y modo **oscuro**
- **Iconos** de archivos
- Muchos **temas** disponibles
- **URLs limpias** equivalentes a las rutas del sistema de archivos
- **Bajo consumo de memoria** (~10MB)
- Configuración sencilla usando una única imagen de **Docker**
- Diseño **responsivo** para dispositivos móviles y escritorio
- Fácilmente configurable mediante **variables de entorno**
- Estadísticas de archivos como fechas de modificación y tamaños
- Soporte para JS y CSS personalizados
- Resaltar archivos actualizados recientemente
- Seguimiento del tiempo de las solicitudes
- Soporte para **arm64**

## Inicio rápido (Docker)

Sirva una carpeta local en modo solo lectura en http://localhost:8080:

```bash
docker run -d \
  --name dir-browser \
  -p 8080:80 \
  -v /my/local/folder:/var/www/html/public:ro \
  -v rdb:/var/lib/redis/ \
  adrianschubek/dir-browser:latest
```

Establezca `PUBLIC_ROOT` en una ruta dentro de `/var/www/html/public` cuando la raíz del contenido montado esté anidada, por ejemplo
`-e PUBLIC_ROOT=/var/www/html/public/link` para un volumen compartido git-sync.

## Documentación

- Documentación: https://dir.adriansoftware.de
- Demostración: https://dir-demo.adriansoftware.de
