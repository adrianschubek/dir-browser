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
- Extremly **fast** file serving through **nginx**
- **README** markdown rendering support
- **JSON API** for programmatic access
- **Batch download** of files and folders in a zip archive
- **file integrity** check with **hashes**
- **custom description** and **labels** for files and folders
- **Search** and **sorting** built-in
- **Password** protection
- **Hide** files and folders
- Light and **Darkmode**
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
- Works **without JavaScript** enabled

<!-- 
v1.1
  add reaedme markdown thephpleague/commonmark renderer !!cache!!
  fix santiaizte inout url 

v1.2
  add ignore pattern
  add remove attribution option
  add password protection
 
tbd
  add file stats
  themes bootswatch

TODO https://github.com/TechEmpower/FrameworkBenchmarks/blob/master/frameworks/PHP/php-ngx/deploy/nginx.conf#L49
diretly embed pohpo in nginx maximum performance

== BUGS ==

- when path contains a dot it triggers full reload -> turbolinks

TODO: file versions support -> dbmeta.json
TODO: sidebar/modal when clicking on file (optional ENV)
TODO:  - ?preview file preview
        - nur modal mit overflow runter scrolling besser!!
TODO: maybe persisten sidebar 2:1 layout -> on click(nicht hover) file show live preview + info + download button
      - on mobile show modal instead

TODO: add search php glob() . add nginx ratelimit
    TODO --- pass IP from host to container for ratelimit!!

TODO Features:

      ?action=download ?action=view
      ?action=hash
      - add hash using hash_file() !!!

      - end to end encrypted files
      - global config file`.dbmeta.json`in root folder
- add password protection for folders
  - cache db meta fields in redis for faster access ! not needed
  - use supervisord php background job -> load dbmeta every 30seconds into redis
  - add password protection for files- migrate from redis to dragonfly ! more latency. not needed right now
    - mnot redis. filesystem file_get_contents is faster!!!
    - maybe drop redis in favor of sqlite (in-memory). slqite only 1 writer at a time (bad). keep redis.

- replace github utpp download with npm i -g utpp

TODO: add info (on hover) and multi select buttons
- multiselect with dropdown button group


-->
