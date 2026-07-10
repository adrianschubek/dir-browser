  $[if `process.env.README_RENDER === "true" && process.env.README_FIRST === "true"`]$
    <?php
      if (isset($readme_render)) {
    ?>
    <div class="container pt-3">
      <div class="card rounded  p-3 markdown-body-light markdown-body-dark" id="readme">
        <?= $readme_render ?>
      </div>
    </div>
    <?php 
    }
    ?>
  $[end]$
  <div class="container py-3">    
    <?php if (defined("AUTH_REQUIRED")) { ?>
      <div class="card rounded  m-auto" style="max-width: 500px;">
        <div class="card-body">
          <h4 class="alert-heading key-icon"><?= (defined('AUTH_RESOURCE') && AUTH_RESOURCE === 'folder') ? 'Protected folder' : 'Protected file' ?></h4>
          <p class="mb-2 text-muted"><?= htmlspecialchars($request_uri) ?></p>
          <p class="mb-2">Please enter the password to access this content.</p>
          <?php if (defined('AUTH_ERROR')) { ?>
            <div class="alert alert-danger py-2" role="alert">Incorrect password.</div>
          <?php } ?>
          <form method="post" data-turbo="false">
            <input autofocus type="password" class="form-control mb-2 rounded" id="key" name="key" required>
            <button type="submit" class="btn rounded btn-primary key-icon form-control">Unlock</button>
          </form>
        </div>
        <div class="card-footer text-center">
          <a href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$/')) ?>">Back to Home</a>
        </div>
      </div>
    <?php } else if (!$path_is_dir) { ?>
      <div class="card rounded  m-auto" style="max-width: 500px;">
        <div class="card-body text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-unknown" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
            <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
            <path d="M12 17v.01"></path>
            <path d="M12 14a1.5 1.5 0 1 0 -1.14 -2.474"></path>
          </svg>
          Not Found<br>
          <a class="btn rounded btn-secondary mt-2" href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$/')) ?>">Back to Home</a>
        </div>
      </div>

    <?php } else { ?>
      <div class="rounded position-sticky card workspace-header px-3" style="top:0; z-index: 5;border-bottom-left-radius: 0 !important;border-bottom-right-radius: 0 !important;">
        <div class="row db-row py-2 text-muted">          
          <div class="col" id="path">
            <a href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$/')) ?>">/</a><?php
            // create links e.g. from ["foo","bar","foobar"] to ["/foo", "/foo/bar", "/foo/bar/foobar"]
            $urls = [];
            foreach ($url_parts as $i => $part) {
              $urls[] = end($urls) . '/' . $part;
              // var_dump($i, $part, $urls);
              $crumb_href = with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$' . $pathPolicy->encodeUrlPath($urls[$i]) . '/');
              echo '<a style="vertical-align: middle;" href="' . htmlspecialchars($crumb_href) . '">' . htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '/</a>';
            }
            ?>
          </div>
          <div class="col-auto pe-0">
            <?php
              $show_logout = isset($access) && is_array($access) && (($access['requires_password'] ?? false) === true);
              $logout_href = '';
              if ($show_logout) {
                $logout_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
                $logout_query = [];
                parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $logout_query);
                $logout_query['logout'] = '1';
                $logout_qs = http_build_query($logout_query);
                $logout_href = with_auth_query_param($logout_path . ($logout_qs !== '' ? ('?' . $logout_qs) : ''));
              }
            ?>
            <?php if ($show_logout) { ?>
              <a href="<?= htmlspecialchars($logout_href) ?>" class="btn rounded btn-sm text-muted" id="icon" title="Logout" data-turbo="false"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-logout"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg></a>
            <?php } ?>
            $[if `process.env.BATCH_DOWNLOAD === "true"`]$
            <button type="button" class="btn rounded btn-sm text-muted multiselect" onclick="downloadMultiple()" title="Download selected files" aria-label="Download selected files">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
            </button>
            <button type="button" class="btn rounded btn-sm text-muted" onclick="toggleMultiselect()" title="Select multiple files" aria-label="Select multiple files">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path stroke="none" d="M0 0h24v24H0z" /><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2 2 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /><path d="M11 14l2 2l4 -4" /></svg>
            </button>
            <button type="button" class="btn rounded btn-sm text-muted" onclick="downloadThisFolder(<?= htmlspecialchars(json_encode($request_uri), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)" title="Download this folder" aria-label="Download this folder">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-folder-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-7a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v3.5" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" /></svg>
            </button>
            $[end]$
            $[if `process.env.SEARCH === "true"`]$
            <button type="button" class="btn rounded btn-sm text-muted" onclick="toggleSearch()" title="Search in <?= htmlspecialchars($request_uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="Search this folder">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
            </button>            
            $[end]$
            <button type="button" class="btn rounded btn-sm text-muted" data-color-toggler onclick="toggletheme()" title="Toggle color theme" aria-label="Toggle color theme">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-moon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" /></svg>
            </button>
          </div>
        </div>
        <div class="row db-row py-2 text-muted d-none" id="search-container">
          <div class="col">
            <div class="input-group">
              <input type="search" class="form-control rounded-start-3" placeholder="Search in <?= htmlspecialchars($request_uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" id="search" aria-label="Search files in this folder" autocomplete="off">
              <select class="form-select rounded-end-3" aria-label="Search engine" id="searchengine" style="max-width:7em">
                $[if `process.env.SEARCH_ENGINE.includes("s")`]$
                <option value="s">Simple</option>
                $[end]$
                $[if `process.env.SEARCH_ENGINE.includes("g")`]$
                <option value="g">Glob</option>
                $[end]$
                $[if `process.env.SEARCH_ENGINE.includes("r")`]$
                <option value="r">Regex</option>
                $[end]$
              </select>
            </div>
          </div>
        </div>
        <div class="row db-row py-2 text-muted" id="sort">
          $[if `process.env.BATCH_DOWNLOAD === "true"`]$
          <div class="col col-auto multiselect" style="display:none">
            <input id="selectall" class="form-check-input" style="padding:5px" type="checkbox" aria-label="Select all visible files" />
            <!-- (un)select all -->
          </div>
          $[end]$
          <a href="" class="col" id="name">Name<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>
          $[if `process.env.DOWNLOAD_COUNTER === "true"`]$<a href="" class="col col-auto text-end d-none d-md-inline-block" id="dl">Downloads<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>$[end]$
          <a href="" class="col col-2 text-end d-none d-md-inline-block" id="size">Size<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>
          <a href="" class="col col-2 text-end d-none d-md-inline-block" id="mod">Modified<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>
        </div>
      </div>
      <div class="rounded card workspace-list d-none" style="border-top: none !important;border-top-right-radius: 0 !important;border-top-left-radius: 0 !important;" id="resultstree"></div>
      <div class="rounded card workspace-list" style="border-top: none !important;border-top-right-radius: 0 !important;border-top-left-radius: 0 !important;" id="filetree">
        
        <?php
        $now = new DateTime();
        foreach ($sorted ?? [] as $file) {
          $fileDate = new DateTime($file->modified_date);
          $diff = $now->diff($fileDate)->days;
        ?>
        <a data-turbo-prefetch="<?= $file->is_dir ? "${{env:PREFETCH_FOLDERS}}$" : "${{env:PREFETCH_FILES}}$" ?>" data-turbo-action="advance" data-file-selected="0" data-file-isdir="<?= $file->is_dir ? "1" : "0" ?>" data-auth-required="<?= ($file->is_dir && $file->auth_required) ? "1" : "0" ?>" data-auth-locked="<?= ($file->is_dir && $file->auth_locked) ? "1" : "0" ?>" data-file-name="<?= htmlspecialchars($file->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-file-dl="$[if `process.env.DOWNLOAD_COUNTER === "true"`]$<?= $file->dl_count ?>$[end]$" data-file-size="<?= $file->size ?>" data-file-mod="<?= $file->modified_date ?>" href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$' . $pathPolicy->encodeUrlPath($file->url) . ($file->is_dir ? '/' : ''))) ?>" class="row db-row py-2 db-file">
          <div class="col col-auto multiselect" style="display:none">
            <input class="form-check-input" style="padding:5px;pointer-events:none" type="checkbox" aria-label="Select <?= htmlspecialchars($file->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" />
          </div>
          <div class="col col-auto pe-0">
          <?php if ($file->name === "..") { ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-corner-left-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M18 18h-6a3 3 0 0 1 -3 -3v-10l-4 4m8 0l-4 -4"></path>
              </svg>
            <?php } elseif ($file->is_dir) { ?>
              <div class="dir-icon-placeholder" dirname="<?= htmlspecialchars($file->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M9 3a1 1 0 0 1 .608 .206l.1 .087l2.706 2.707h6.586a3 3 0 0 1 2.995 2.824l.005 .176v8a3 3 0 0 1 -2.824 2.995l-.176 .005h-14a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-11a3 3 0 0 1 2.824 -2.995l.176 -.005h4z" stroke-width="0" fill="currentColor"></path>
                </svg>
              </div>
            <?php } else { ?>
              <div class="file-icon-placeholder" filename="<?= htmlspecialchars($file->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                  <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
                </svg>
              </div>
            <?php } ?>
            </div> 
            <div class="col">
            <?= htmlspecialchars($file->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            <?php if ($file->auth_locked) { ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="var(--muted)" class="icon icon-tabler icons-tabler-filled icon-tabler-shield-lock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.998 2l.118 .007l.059 .008l.061 .013l.111 .034a.993 .993 0 0 1 .217 .112l.104 .082l.255 .218a11 11 0 0 0 7.189 2.537l.342 -.01a1 1 0 0 1 1.005 .717a13 13 0 0 1 -9.208 16.25a1 1 0 0 1 -.502 0a13 13 0 0 1 -9.209 -16.25a1 1 0 0 1 1.005 -.717a11 11 0 0 0 7.531 -2.527l.263 -.225l.096 -.075a.993 .993 0 0 1 .217 -.112l.112 -.034a.97 .97 0 0 1 .119 -.021l.115 -.007zm.002 7a2 2 0 0 0 -1.995 1.85l-.005 .15l.005 .15a2 2 0 0 0 .995 1.581v1.769l.007 .117a1 1 0 0 0 1.993 -.117l.001 -1.768a2 2 0 0 0 -1.001 -3.732z" /></svg>
            <?php } ?>
            <?php 
              if ($file->meta !== null) {
                if (isset($file->meta->description)) {
            ?> 
                  <span class="text-body-secondary"><?= $file->meta->description ?></span>
            <?php
                }
                if (isset($file->meta->labels) && is_array($file->meta->labels)) {
                  foreach ($file->meta->labels as $lbl) {
                    $l = explode(":", $lbl, 2);
            ?>
                    <span class="badge bg-<?= htmlspecialchars($l[0] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($l[1] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <?php
                  }
                }
                // per-file password protection via .dbmeta.json was removed in favor of folder-level .access.json
              }
            ?>
            </div>
            <?php if (!$file->is_dir) { ?>
            <div class="col col-auto text-end">
              $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
              <span title="Total downloads" class="text-muted ms-auto d-none d-md-inline rounded-1 text-end px-1 <?= $file->dl_count === 0 ? "text-body-tertiary" : "" ?>">
                <?= numsize($file->dl_count) ?>
                <svg style="margin-top: -5px;" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                  <path d="M7 11l5 5l5 -5"></path>
                  <path d="M12 4l0 12"></path>
                </svg>
              </span>
              $[end]$
            </div>
            <div class="col col-2 text-end">    
              <span title="File size" class="ms-auto d-none d-md-inline rounded-1 text-end px-1">
                <?= human_filesize($file->size) ?>
              </span>
            </div>
            <?php } ?>
            <div class="col col-2">
              <span title="Last modified on <?= $file->modified_date ?>" class="d-none d-md-block text-end filedatetime" ${{`process.env.HIGHLIGHT_UPDATED !== "false" && 'style="font-weight:<?= ($diff > 2 ? "normal !important;": "bold !important;") ?>"'`}}$>
                <?= $file->modified_date ?>
              </span>
            </div>
          </a>

        <?php
        }
        ?>

        <?php if (count($sorted_files) === 0 && (count($sorted_folders) === 0 || count($sorted_folders) === 1 && $sorted_folders[0]->name === "..")) { ?>
          <div class="list-group-item text-center py-3" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
              <path d="M3 3l18 18"></path>
              <path d="M19 19h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 1.172 -1.821m3.828 -.179h1l3 3h7a2 2 0 0 1 2 2v8"></path>
            </svg>
            Empty Folder
          </div>
        <?php } ?>
      </div>
    <?php } ?>
  </div>

  $[if `process.env.README_RENDER === "true" && process.env.README_FIRST === "false"`]$
  <?php
    if (isset($readme_render)) {
  ?>
  <div class="container pb-3">
    <div class="card rounded p-3 markdown-body-light markdown-body-dark" id="readme">
      <?= $readme_render ?>
    </div>
  </div>
  <?php 
  }
  ?>
  $[end]$

  <?php if ($max_pages > 1) { ?>
  <div class="container pb-3" style="display:flex;justify-content:center;">
    <nav aria-label="Directory pages">
    <ul class="pagination">
      <li class="page-item"><a data-turbo-prefetch="false" class="page-link <?= $current_page <= 1 ? "disabled" : "" ?>" href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$' . $request_href_path . '?p=' . ($current_page - 1))) ?>"><svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="iconX icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg></a></li>
      <?php foreach ($pages as $p) { ?>
      <li class="page-item"><a data-turbo-prefetch="false" class="page-link <?= $p == $current_page ? "active" : "" ?> <?= $p == ".." ? "disabled" : "" ?>" href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$' . $request_href_path . '?p=' . ($p))) ?>"><?= $p ?></a></li>
      <?php } ?>
      <li class="page-item"><a data-turbo-prefetch="false" class="page-link <?= $current_page >= $max_pages ? "disabled" : "" ?>" href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$' . $request_href_path . '?p=' . ($current_page + 1))) ?>"><svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="iconX icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg></a></li>
    </ul>
    </nav>
  </div>
  <?php } ?>

  <div class="mt-auto">
    <div class="container py-2 text-center" id="footer">
      Displaying <?= $display_start ?? 0 ?>-<?= $display_end ?? 0 ?> of <?= $total_items ?> | <?= human_filesize($total_size) ?> $[if `process.env.TIMING === "true"`]$| <?= (hrtime(true) - $time_start)/1000000 ?> ms $[end]$$[if `process.env.API === "true"`]$| <a href="<?= htmlspecialchars(with_auth_query_param('${{`process.env.BASE_PATH ?? ''`}}$' . $request_href_path . '?ls')) ?>" target="_blank" rel="noopener" aria-label="Open directory API response"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-api" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 13h5" /><path d="M12 16v-8h3a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-3" /><path d="M20 8v8" /><path d="M9 16v-5.5a2.5 2.5 0 0 0 -5 0v5.5" /></svg></a>$[end]$<br>
    <span style="opacity:0.8"><span style="opacity: 0.8;">Powered by</span>  <a href="https://dir.adriansoftware.de" class="text-decoration-none text-primary" target="_blank">dir-browser</a> v<?= VERSION ?></span>  
    </div>
  </div>

  $[if `process.env.LAYOUT === "popup"`]$
  <div class="modal fade" id="file-popup" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-lg-down modal-lg">
      <div class="modal-content rounded ">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="staticBackdropLabel">Modal title</h1>
          <button type="button" class="btn-close" id="file-popup-x" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="file-popup-preview" class="mb-3">
            <div class="text-body-secondary">Loading…</div>
          </div>
          <div class="border-top pt-2">
            <div class="fw-semibold mb-2">Metadata</div>
            <dl class="row mb-0" id="file-popup-meta"></dl>
          </div>
        </div>
        <div class="modal-footer">
        <!-- TODO: add copy button if kind == text -->
        <button id="file-popup-copy" type="button" class="btn rounded btn-secondary" disabled><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> Copy Text</button>

        ${{`process.env.API === "true" ? '<a id="file-info-url-api" href="?info" target="_blank" type="button" class="btn rounded btn-secondary"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-code"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 8l-4 4l4 4" /><path d="M17 8l4 4l-4 4" /><path d="M14 4l-4 16" /></svg> API</a>' : ''`}}$
          <a id="file-info-url" type="button" class="btn rounded btn-primary"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg> Download</a>
        </div>
      </div>
    </div>
  </div>
  $[end]$

  <!-- Password auth popup for protected folders (keeps full-page prompt as fallback) -->
  <div class="modal fade" id="auth-popup" tabindex="-1" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down" style="max-width: 520px;">
      <div class="modal-content rounded ">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="auth-popup-title">Protected folder</h1>
          <button type="button" class="btn-close" id="auth-popup-x" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-body-secondary mb-2">Enter the password to access this folder.</div>
          <div class="alert alert-danger py-2 d-none" id="auth-popup-error" role="alert">Incorrect password.</div>
          <form id="auth-popup-form" data-turbo="false">
            <input type="password" class="form-control mb-2 rounded" id="auth-popup-key" name="key" autocomplete="current-password" required>
            <button type="submit" class="btn rounded btn-primary key-icon form-control" id="auth-popup-submit">Unlock</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Toasts -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3 border-4 rounded" style="z-index: 1100;">
    <div id="batch-download-toast" class="toast border-4 rounded" role="alert" aria-live="polite" aria-atomic="true">
      <div class="toast-header">
        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-folder-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-7a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v3.5" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" /></svg>
        <strong class="me-auto ms-1" id="batch-download-toast-header">...</strong>
        <button type="button" class="btn-close" data-toast-dismiss aria-label="Close"></button>
      </div>
      <div class="toast-body" id="batch-download-toast-body">...</div>
    </div>
  </div>

  <!-- Powered by https://github.com/adrianschubek/dir-browser -->
