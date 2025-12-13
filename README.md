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

  Visit [dir.adriansoftware.de](https://dir.adriansoftware.de) for documentation & more! 

</h2>

<!-- ![image](https://github.com/adrianschubek/dir-browser/assets/19362349/102e058f-7d9e-457f-bde5-d61a8b0733f7) -->

<!-- <img src="https://github.com/adrianschubek/dir-browser/assets/19362349/102e058f-7d9e-457f-bde5-d61a8b0733f7" alt="" style="
    /* overflow: hidden; */
    object-fit: scale-down;
    width: 100%;
"> -->


## Demo

https://dir-demo.adriansoftware.de

## Features
- **Download counter** for all files
- Secure by default. **Read-only** access
- Extremely **fast** file serving through **nginx**
- **README** markdown rendering support
- **JSON API** for programmatic access
- **Batch download** of files and folders in a zip archive
- **File integrity** checks with **hashes**
- **Custom descriptions** and **labels** for files and folders
- **Search** and **sorting** built-in
- **Password** protection
- **Hide** files and folders
- Light and **Dark mode**
- File **icons**
- Many **Themes** available
- **Clean URLs** equivalent to file system paths
- **Low memory** footprint (~10MB)
- Easy setup using single **Docker** image
- **Responsive** design for mobile devices and desktop
- Easily configurable using **environment variables**
- File stats like modification dates and sizes
- Custom JS and CSS support
- Highlight recently updated files
- Track request timing
- **arm64** support

## Quick start (Docker)

Serve a local folder read-only at http://localhost:8080:

```bash
docker run -d \
  --name dir-browser \
  -p 8080:80 \
  -v /my/local/folder:/var/www/html/public:ro \
  -v rdb:/var/lib/redis/ \
  adrianschubek/dir-browser:latest
```

## Documentation

- Docs: https://dir.adriansoftware.de
- Demo: https://dir-demo.adriansoftware.de
