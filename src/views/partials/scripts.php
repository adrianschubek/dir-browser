  <script data-turbo-eval="false">
    const showToast = (message, header) => {
      try {
        const toastEl = document.getElementById('batch-download-toast');
        const toastBody = document.getElementById('batch-download-toast-body');
        if (!toastEl || !toastBody) return;
        toastBody.textContent = String(message ?? '');
        if (header) {
          const toastHeader = document.getElementById('batch-download-toast-header');
          if (toastHeader) {
            toastHeader.textContent = String(header);
          }
        }
        window.clearTimeout(toastEl._hideTimer);
        toastEl.classList.add('show');
        toastEl._hideTimer = window.setTimeout(() => toastEl.classList.remove('show'), 13000);
      } catch (e) {
        // no-op
      }
    };

    const copyTextToClipboard = async (text) => {
      if (typeof text !== 'string' || text.length === 0) return false;

      // Prefer async clipboard API when available (requires secure context).
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(text);
          return true;
        }
      } catch (e) {
        // Fall back below.
      }

      // HTTP / non-secure fallback using execCommand.
      try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-1000px';
        textarea.style.left = '-1000px';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
      } catch (e) {
        return false;
      }
    };

    const AUTH_QUERY_VALUE = new URLSearchParams(window.location.search).get('auth');

    const withAuthQuery = (urlString) => {
      if (typeof urlString !== 'string' || urlString.length === 0) return urlString;
      if (!AUTH_QUERY_VALUE || AUTH_QUERY_VALUE.length === 0) return urlString;
      const url = new URL(urlString, window.location.href);
      if (url.origin !== window.location.origin) return urlString;
      if (!url.searchParams.has('auth')) {
        url.searchParams.set('auth', AUTH_QUERY_VALUE);
      }
      return url.toString();
    };

    $[if `process.env.DATE_FORMAT === "relative"`]$
    function getRelativeTimeString(date, lang = navigator.language) {
      const timeMs = typeof date === "number" ? date : date.getTime();
      const deltaSeconds = Math.round((timeMs - Date.now()) / 1000);

      const cutoffs = [60, 3600, 86400, 86400 * 7, 86400 * 30, 86400 * 365, Infinity];
      const units = ["second", "minute", "hour", "day", "week", "month", "year"];

      // Find the ideal cutoff unit by iterating manually
      let unitIndex = 0;
      while (unitIndex < cutoffs.length && Math.abs(deltaSeconds) >= cutoffs[unitIndex]) {
        unitIndex++;
      }

      // Calculate the time difference in the current unit
      const timeInCurrentUnit = Math.abs(deltaSeconds) / (unitIndex ? cutoffs[unitIndex - 1] : 1);

      // Adjust the displayed time based on the 50% threshold
      const adjustedTime = Math.floor(timeInCurrentUnit);

      // Include the negative sign for time that has passed
      const sign = deltaSeconds < 0 ? "-" : "";

      const rtf = new Intl.RelativeTimeFormat(lang, { numeric: "auto" });
      return rtf.format(sign + adjustedTime, units[unitIndex]);
    }
    $[end]$

    $[if `process.env.HASH === "true"`]$
    // via api bc otherwise we need to include the hash in the tree itself which is costly
    const HASH_MAX_FILE_SIZE_MB = Number('${{`process.env.HASH_MAX_FILE_SIZE_MB ?? ""`}}$');
    const HASH_MAX_FILE_SIZE_BYTES = Number.isFinite(HASH_MAX_FILE_SIZE_MB) && HASH_MAX_FILE_SIZE_MB > 0
      ? Math.floor(HASH_MAX_FILE_SIZE_MB * 1024 * 1024)
      : null;

    const getHashViaApi = async (url) => {
      const res = await fetch(withAuthQuery(url));
      if (!res.ok) {
        if (res.status === 413) throw new Error('too_large');
        throw new Error('request_failed');
      }
      const data = await res.json();
      const hash = data.hash_${{`process.env.HASH_ALGO`}}$;
      if (hash === null || hash === undefined || String(hash).length === 0) throw new Error('unavailable');
      const text = String(hash ?? '');
      await copyTextToClipboard(text);
      return text;
    }
    $[end]$

    // Batch download
    $[if `process.env.BATCH_DOWNLOAD === "true"`]$
    const download = async (all) => {
      // create form and submit
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = withAuthQuery('${{`process.env.BASE_PATH ?? ''`}}$');
      form.style.display = 'none';
      const basePath = '${{`process.env.BASE_PATH ?? ''`}}$';
      document.querySelectorAll('.db-file').forEach((file) => {
        if ((all || file.getAttribute('data-file-selected') === "1") && file.getAttribute('data-file-name') !== "..") {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'download_batch[]';
          // Always send just the path (relative to dir-browser PUBLIC_FOLDER), without BASE_PATH and without query params.
          const href = file.getAttribute('href');
          const url = new URL(href, window.location.origin);
          let path = url.pathname;
          if (basePath && path.startsWith(basePath)) {
            path = path.slice(basePath.length) || '/';
          }
          input.value = path;
          form.appendChild(input);          
        }
      });
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    }
    const downloadThisFolder = async (path) => {
      showToast('Preparing batch download. This may take a few moments...', path);

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = withAuthQuery('${{`process.env.BASE_PATH ?? ''`}}$');
      form.style.display = 'none';

      const basePath = '${{`process.env.BASE_PATH ?? ''`}}$';
      const url = new URL(path, window.location.origin);
      let folderPath = url.pathname;

      if (basePath && folderPath.startsWith(basePath)) {
        folderPath = folderPath.slice(basePath.length) || '/';
      }

      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'download_batch[]';
      input.value = folderPath;
      form.appendChild(input);

      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    }
    const downloadMultiple = async () => {
      const selectedCount = document.querySelectorAll('.db-file[data-file-selected="1"]').length;
      if (selectedCount === 0) {
        showToast('Select at least one file or folder first.', 'Nothing selected');
        return;
      }
      showToast('Preparing batch download. This may take a few moments...', `${selectedCount} selected`);
      await download(false);
    }
    const toggleMultiselect = () => {
      const local = localStorage.getItem("multiSelectMode");
      let multiSelectMode = local === null ? false : local === "true";
      multiSelectMode = !multiSelectMode;
      updateMultiselect(multiSelectMode);
      localStorage.setItem("multiSelectMode", multiSelectMode);
    }
    const toggleSelectAll = (e) => {
      if (e.target.checked) {
        document.querySelectorAll('.db-file').forEach((file) => {
          if (file.getAttribute('data-file-name') !== "..") {
            file.setAttribute("data-file-selected", "1");
            file.querySelector('input').checked = true; /* checkbox */
          }
        });
      } else {
        document.querySelectorAll('.db-file').forEach((file) => {
          if (file.getAttribute('data-file-name') !== "..") {
            file.setAttribute("data-file-selected", "0");
            file.querySelector('input').checked = false; /* checkbox */
          }
        });
      }
    }
    const dbItemClickListener = async (e) => {
      e.preventDefault();
      const file = e.target.closest('a');
      if (file.getAttribute("data-file-selected") === "1") {
        file.setAttribute("data-file-selected", "0");
        file.querySelector('input').checked = false; /* checkbox */
      } else {
        file.setAttribute("data-file-selected", "1");
        file.querySelector('input').checked = true;
      }
    }
    const updateMultiselect = (multi) => {
      if (multi) document.querySelector("#selectall").addEventListener('change', toggleSelectAll);
      else document.querySelector("#selectall").removeEventListener('change', toggleSelectAll);
      const selects = document.querySelectorAll('.multiselect');
      const files = document.querySelectorAll('.db-file');
      selects.forEach((select) => {
        if (multi) {
          select.style.display = 'inline-block';
        } else {
          select.style.display = 'none';
        }
      });
      files.forEach((file) => {
        // skip ".." folder
        if (file.getAttribute('data-file-name') === "..") {
          return;
        }
        // disable link
        if (multi) {
          // file.setAttribute("data-file-selected", "1")
          file.addEventListener('click', dbItemClickListener);
        } else {
          // file.setAttribute("data-file-selected", "0")
          file.removeEventListener('click', dbItemClickListener);
        }
      })
    }
    $[end]$

    $[if `process.env.SEARCH === "true"`]$
    const createSearchResult = (result) => {
      const item = document.createElement('a');
      item.classList.add('list-group-item', 'list-group-item-action', 'db-file');
      item.href = withAuthQuery("${{`process.env.BASE_PATH ?? ''`}}$" + (result.href || result.url));
      item.setAttribute('data-file-name', String(result.name ?? ''));
      item.setAttribute('data-file-isdir', result.is_dir ? '1' : '0');
      if (result.is_dir) {
        item.setAttribute('data-auth-required', result.auth_required ? '1' : '0');
        item.setAttribute('data-auth-locked', result.auth_locked ? '1' : '0');
      }
      const row = document.createElement('div');
      row.className = 'row py-2 ';

      const iconCol = document.createElement('div');
      iconCol.className = 'col col-auto pe-0';

      const iconWrapper = document.createElement('div');
      iconWrapper.className = 'file-icon-placeholder';
      iconWrapper.setAttribute('filename', String(result.name ?? ''));

      if (result.is_dir) {
        const dirWrapper = document.createElement('div');
        dirWrapper.className = 'dir-icon-placeholder';
        dirWrapper.setAttribute('dirname', '');
        dirWrapper.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M9 3a1 1 0 0 1 .608 .206l.1 .087l2.706 2.707h6.586a3 3 0 0 1 2.995 2.824l.005 .176v8a3 3 0 0 1 -2.824 2.995l-.176 .005h-14a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-11a3 3 0 0 1 2.824 -2.995l.176 -.005h4z" stroke-width="0" fill="currentColor"></path></svg>';
        iconWrapper.appendChild(dirWrapper);
      } else {
        iconWrapper.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M14 3v4a1 1 0 0 0 1 1h4"></path><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path></svg>';
      }

      iconCol.appendChild(iconWrapper);

      const nameCol = document.createElement('div');
      nameCol.className = 'col';
      nameCol.textContent = String(result.name ?? '');

      if (result.auth_locked) {
        nameCol.appendChild(document.createTextNode(' '));
        const lockWrap = document.createElement('span');
        lockWrap.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="var(--muted)" class="icon icon-tabler icons-tabler-filled icon-tabler-shield-lock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.998 2l.118 .007l.059 .008l.061 .013l.111 .034a.993 .993 0 0 1 .217 .112l.104 .082l.255 .218a11 11 0 0 0 7.189 2.537l.342 -.01a1 1 0 0 1 1.005 .717a13 13 0 0 1 -9.208 16.25a1 1 0 0 1 -.502 0a13 13 0 0 1 -9.209 -16.25a1 1 0 0 1 1.005 -.717a11 11 0 0 0 7.531 -2.527l.263 -.225l.096 -.075a.993 .993 0 0 1 .217 -.112l.112 -.034a.97 .97 0 0 1 .119 -.021l.115 -.007zm.002 7a2 2 0 0 0 -1.995 1.85l-.005 .15l.005 .15a2 2 0 0 0 .995 1.581v1.769l.007 .117a1 1 0 0 0 1.993 -.117l.001 -1.768a2 2 0 0 0 -1.001 -3.732z" /></svg>';
        if (lockWrap.firstElementChild) {
          nameCol.appendChild(lockWrap.firstElementChild);
        }
      }

      row.appendChild(iconCol);
      row.appendChild(nameCol);
      item.appendChild(row);
      return item;
    }
    let searchTimer = null;
    let searchController = null;

    const renderSearchMessage = (message, className = 'text-body-secondary') => {
      const results = document.querySelector('#resultstree');
      results.replaceChildren();
      const messageNode = document.createElement('div');
      messageNode.className = `row py-3 justify-content-center ${className}`;
      messageNode.setAttribute('role', 'status');
      messageNode.textContent = message;
      results.appendChild(messageNode);
    };

    const runSearch = async () => {
      const query = document.querySelector('#search').value.trim();
      const engine = document.querySelector('#searchengine').value;
      if (query.length === 0) {
        searchController?.abort();
        renderSearchMessage('Start typing to search this folder.');
        return;
      }

      searchController?.abort();
      searchController = new AbortController();
      renderSearchMessage('Searching…');

      const apiUrl = new URL(window.location.href);
      apiUrl.search = '';
      apiUrl.searchParams.set('q', query);
      apiUrl.searchParams.set('e', engine);
      const authenticatedUrl = withAuthQuery(apiUrl.toString());

      try {
        const response = await fetch(authenticatedUrl, { signal: searchController.signal });
        const api = await response.json();
        if (!response.ok) throw new Error(api.error || 'Search failed');

        const results = document.querySelector('#resultstree');
        results.replaceChildren();
        const summary = document.createElement('div');
        summary.className = 'row py-2 justify-content-center text-body-secondary';
        summary.setAttribute('role', 'status');
        summary.textContent = api.truncated
          ? 'Result limit reached. Narrow down your query.'
          : `${api.total} result${api.total === 1 ? '' : 's'}`;
        results.appendChild(summary);
        api.results.forEach((result) => results.appendChild(createSearchResult(result)));
      } catch (error) {
        if (error.name !== 'AbortError') renderSearchMessage(error.message || 'Search failed.', 'text-danger');
      }
    };

    const search = () => {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(runSearch, 250);
    };
    const toggleSearch = () => {
      const search = document.querySelector('#search-container');
      search.classList.toggle('d-none');
      const filetree = document.querySelector('#filetree');
      filetree.classList.toggle('d-none');
      const resultstree = document.querySelector('#resultstree');
      resultstree.classList.toggle('d-none');
      if (!search.classList.contains('d-none')) {
        document.querySelector('#search').focus();
      }
    };
    $[end]$

    // Auth popup for protected folders
    const authPopupState = {
      targetUrl: '',
      title: 'Protected folder',
      returnFocus: null,
    };

    const showAuthPopup = (url, title) => {
      authPopupState.targetUrl = url;
      authPopupState.title = title || 'Protected folder';
      const popup = document.querySelector('#auth-popup');
      const titleNode = document.querySelector('#auth-popup-title');
      const keyInput = document.querySelector('#auth-popup-key');
      const err = document.querySelector('#auth-popup-error');
      const submit = document.querySelector('#auth-popup-submit');
      if (!popup) return;
      authPopupState.returnFocus = document.activeElement;
      if (titleNode) titleNode.textContent = authPopupState.title;
      if (err) err.classList.add('d-none');
      if (submit) submit.disabled = false;
      if (keyInput) keyInput.value = '';

      popup.classList.add('d-block');
      popup.classList.add('show');
      popup.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
      setTimeout(() => keyInput?.focus(), 50);
    };

    const hideAuthPopup = () => {
      const popup = document.querySelector('#auth-popup');
      if (!popup) return;
      popup.classList.remove('d-block');
      popup.classList.remove('show');
      popup.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
      authPopupState.returnFocus?.focus?.();
    };

    document.addEventListener('click', (e) => {
      const a = e.target?.closest ? e.target.closest('a.db-file') : null;
      if (!a) return;
      // Only for folders that are locked.
      if (a.getAttribute('data-file-isdir') !== '1') return;
      if (a.getAttribute('data-auth-locked') !== '1') return;
      // If multiselect mode is on, keep existing multiselect behavior.
      if ((localStorage.getItem('multiSelectMode') ?? 'false') === 'true') return;
      e.preventDefault();
      const name = a.getAttribute('data-file-name') || 'Protected folder';
      showAuthPopup(a.href, name);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && document.querySelector('#auth-popup.show')) hideAuthPopup();
    });

    // Delegated handlers (survive Turbo navigation / DOM swaps)
    document.addEventListener('click', (e) => {
      const t = e.target;
      if (!t) return;
      if (t.id === 'auth-popup-x') {
        e.preventDefault();
        hideAuthPopup();
        return;
      }
      if (t.id === 'auth-popup') {
        hideAuthPopup();
        return;
      }
    });

    document.addEventListener('submit', async (e) => {
      const form = e.target;
      if (!form || form.id !== 'auth-popup-form') return;
      e.preventDefault();

      const keyInput = document.querySelector('#auth-popup-key');
      const err = document.querySelector('#auth-popup-error');
      const submit = document.querySelector('#auth-popup-submit');
      const key = keyInput?.value ?? '';
      if (err) {
        err.textContent = 'Incorrect password.';
        err.classList.add('d-none');
      }
      if (!authPopupState.targetUrl) return;
      if (!key || key.length === 0) return;

      try {
        if (submit) submit.disabled = true;
        const targetUrl = withAuthQuery(authPopupState.targetUrl);
        const res = await fetch(targetUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: 'key=' + encodeURIComponent(key),
          credentials: 'same-origin',
        });

        if (res.status === 401) {
          if (err) err.classList.remove('d-none');
          if (submit) submit.disabled = false;
          keyInput?.focus();
          keyInput?.select();
          return;
        }

        // Success: cookie should be set; navigate normally.
        window.location.href = targetUrl;
      } catch (ex) {
        if (err) {
          err.textContent = 'Authentication failed. Please try again.';
          err.classList.remove('d-none');
        }
        if (submit) submit.disabled = false;
      }
    });

    const sortElements = (key, elems) => elems.sort((a, b) => {
      const aVal = a.getAttribute(`data-file-${key}`);
      const bVal = b.getAttribute(`data-file-${key}`);
      if (key === 'name') {
        return aVal.localeCompare(bVal);
      } else if (key === 'dl') {
        return parseInt(aVal) - parseInt(bVal);
      } else if (key === 'size') {
        return parseInt(aVal) - parseInt(bVal);
      } else if (key === 'mod') {
        return aVal.localeCompare(bVal);
      }
    });
    const sort = (key, reverse) => {
      const items = Array.from(document.querySelectorAll('.db-file'));
      // seperate sort for folders (is_dir) and files
      const folders = sortElements(key, items.filter((item) => item.getAttribute('data-file-isdir') === '1'));
      const files = sortElements(key, items.filter((item) => item.getAttribute('data-file-isdir') === '0'));
      if (reverse) {
        folders.reverse();
        files.reverse();
      }
      // move .. folder top first position
      const parentFolder = folders.find((item) => item.getAttribute('data-file-name') === '..');
      if (parentFolder) {
        folders.splice(folders.indexOf(parentFolder), 1);
        folders.unshift(parentFolder);
      }
      const sorted = [...folders, ...files];
      items.forEach((item) => item.remove());
      sorted.forEach((item) => document.querySelector('#filetree').appendChild(item));
    };
    $[if `process.env.LAYOUT === "popup"`]$
      const popupState = {
        preview: {
          kind: 'none',
          text: ''
        },
        apiInfoUrl: ''
      };

      const fileUrlWithParam = (urlString, key, value) => {
        const url = new URL(withAuthQuery(urlString), window.location.href);
        url.searchParams.set(key, value);
        return url.toString();
      };

      const setMeta = (entries) => {
        const meta = document.querySelector('#file-popup-meta');
        meta.innerHTML = '';
        entries.forEach(({ label, value }) => {
          const dt = document.createElement('dt');
          dt.className = 'col-4 text-body-secondary';
          dt.textContent = label;
          const dd = document.createElement('dd');
          dd.className = 'col-8';
          if (value instanceof Node) {
            dd.appendChild(value);
          } else {
            dd.textContent = String(value ?? '');
          }
          meta.appendChild(dt);
          meta.appendChild(dd);
        });
      };

      const updateCopyButton = () => {
        const btn = document.querySelector('#file-popup-copy');
        if (!btn) return;
        const canCopy = (popupState.preview.kind === 'text' || popupState.preview.kind === 'json' || popupState.preview.kind === 'csv')
          && typeof popupState.preview.text === 'string'
          && popupState.preview.text.length > 0;
        btn.disabled = !canCopy;
      };

      const copyPreviewToClipboard = async () => {
        await copyTextToClipboard(popupState.preview.text || '');
      };

      const setPreview = (preview, rawUrl) => {
        const node = document.querySelector('#file-popup-preview');
        node.innerHTML = '';

        popupState.preview.kind = preview?.kind ?? 'none';
        popupState.preview.text = '';
        updateCopyButton();

        if (!preview || preview.kind === 'none') {
          const el = document.createElement('div');
          el.className = 'text-body-secondary';
          el.textContent = 'No preview available for this file.';
          node.appendChild(el);
          return;
        }

        if (preview.kind === 'image') {
          const img = document.createElement('img');
          img.className = 'img-fluid rounded';
          img.alt = 'Preview';
          img.src = rawUrl;
          node.appendChild(img);
          return;
        }

        if (preview.kind === 'pdf') {
          const iframe = document.createElement('iframe');
          iframe.className = 'w-100 rounded';
          iframe.style.height = '80vh';
          iframe.src = rawUrl;
          node.appendChild(iframe);
          return;
        }

        if (preview.kind === 'video') {
          const video = document.createElement('video');
          video.className = 'w-100 rounded';
          video.controls = true;
          const source = document.createElement('source');
          source.src = rawUrl;
          source.type = preview.mime || 'video/mp4';
          video.appendChild(source);
          node.appendChild(video);
          return;
        }

        if (preview.kind === 'audio') {
          const audio = document.createElement('audio');
          audio.className = 'w-100';
          audio.controls = true;
          audio.preload = 'metadata';
          const source = document.createElement('source');
          source.src = rawUrl;
          source.type = preview.mime || 'audio/mpeg';
          audio.appendChild(source);
          node.appendChild(audio);
          return;
        }

        if (preview.kind === 'markdown') {
          const div = document.createElement('div');
          div.className = 'markdown-body p-3 rounded';
          div.style.maxHeight = '60vh';
          div.style.overflow = 'auto';
          div.innerHTML = preview.text || '';
          node.appendChild(div);
          return;
        }

        const pre = document.createElement('pre');
        pre.className = 'bg-body-tertiary p-2 rounded small';
        pre.style.maxHeight = '50vh';
        pre.style.overflow = 'auto';
        pre.textContent = preview.text || '';
        node.appendChild(pre);

        popupState.preview.text = pre.textContent;
        updateCopyButton();

        if (preview.truncated) {
          const note = document.createElement('div');
          note.className = 'text-body-secondary small mt-1';
          note.textContent = 'Preview truncated.';
          node.appendChild(note);
        }
      };

      const setFileinfo = async (data) => {
        document.querySelector('#file-popup .modal-title').innerText = data.name;
        const apiBtn = document.querySelector('#file-info-url-api');
        popupState.apiInfoUrl = fileUrlWithParam(data.url, 'info', '');
        if (apiBtn) apiBtn.href = popupState.apiInfoUrl;
        document.querySelector('#file-info-url').href = withAuthQuery(data.url);

        const popup = document.querySelector('#file-popup');
        popup.classList.add('d-block');
        popup.classList.add('show');
        popup.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        const previewNode = document.querySelector('#file-popup-preview');
        previewNode.innerHTML = '<div class="text-body-secondary">Loading…</div>';
        setMeta([]);

        try {
          const previewUrl = fileUrlWithParam(data.url, 'preview', '1');
          const res = await fetch(previewUrl);
          if (!res.ok) throw new Error('Preview request failed');
          const payload = await res.json();
          const rawUrl = fileUrlWithParam(data.url, 'raw', '1');
          setPreview(payload.preview, rawUrl);

          const modified = payload.modified ? new Date(payload.modified).toLocaleString() : '';
          const entries = [
            { label: 'Size', value: payload.size_human ?? String(payload.size ?? '') },
            { label: 'Modified', value: modified },
            { label: 'Downloads', value: String(payload.downloads ?? 0) },
            { label: 'MIME', value: payload.mime ?? '' },
          ];
          $[end]$
          $[if `process.env.LAYOUT === "popup" && process.env.HASH === "true"`]$
          // Show hash entry; actual hash is fetched on demand via API.
          if (typeof getHashViaApi === 'function') {
            const fileSize = Number(payload.size ?? 0);
            const hashingTooLarge = HASH_MAX_FILE_SIZE_BYTES !== null && Number.isFinite(fileSize) && fileSize > HASH_MAX_FILE_SIZE_BYTES;
            if (hashingTooLarge) {
              const note = document.createElement('span');
              note.className = 'text-body-secondary';
              note.textContent = `Too large to hash (>${HASH_MAX_FILE_SIZE_MB} MB)`;
              entries.push({ label: 'Hash (${{`process.env.HASH_ALGO`}}$)', value: note });
            } else {
              const link = document.createElement('a');
              link.href = '#';
              link.className = 'link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover';
              link.textContent = 'Click to calculate hash';
              link.addEventListener('click', async (e) => {
                link.textContent = 'Calculating…';
                e.preventDefault();
                if (!popupState.apiInfoUrl) return;
                try {
                  const hash = await getHashViaApi(popupState.apiInfoUrl);
                  link.textContent = `${hash} (copied to clipboard)`;
                } catch (err) {
                  link.textContent = err && String(err.message) === 'too_large' ? 'Too large to hash' : 'Hash unavailable';
                }
              });
              entries.push({ label: 'Hash (${{`process.env.HASH_ALGO`}}$)', value: link });
            }
          }
          $[end]$
          $[if `process.env.LAYOUT === "popup"`]$

          setMeta(entries);
        } catch (err) {
          previewNode.innerHTML = '<div class="text-danger">Failed to load preview.</div>';
        }
      }
    $[end]$
  </script>
  <script>
    document.querySelectorAll(".filedatetime").forEach(function(element) {
      $[if `process.env.DATE_FORMAT === "utc"`]$
      element.innerHTML = new Date(element.innerHTML.trim()).toISOString().slice(0, 19).replace("T", " ") + " UTC"
      $[if `process.env.DATE_FORMAT === "relative"`]$
      element.innerHTML = getRelativeTimeString(new Date(element.innerHTML.trim())${{`process.env.DATE_FORMAT_RELATIVE_LANG ? ",'"+process.env.DATE_FORMAT_RELATIVE_LANG+"'" : ""`}}$)
      $[else]$
      element.innerHTML = new Date(element.innerHTML.trim()).toLocaleString()
      $[end]$
    })

    // if localstoage has sort:order:name, apply it
    if (localStorage.getItem("sort:order:name")) {
      sort('name', localStorage.getItem("sort:order:name") === "desc");
    } else if (localStorage.getItem("sort:order:dl")) {
      sort('dl', localStorage.getItem("sort:order:dl") === "desc");
    } else if (localStorage.getItem("sort:order:size")) {
      sort('size', localStorage.getItem("sort:order:size") === "desc");
    } else if (localStorage.getItem("sort:order:mod")) {
      sort('mod', localStorage.getItem("sort:order:mod") === "desc");
    }

    // Readme open in new tab fix
    $[if `process.env.OPEN_NEW_TAB === "true"`]$
    document.querySelectorAll("#readme a").forEach((el) => {
      el.setAttribute("target", "_blank");
    });
    $[end]$

    document.querySelectorAll(".stopprop").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        // e.stopImmediatePropagation(); //this breaks stuff
      });
    });

    document.querySelectorAll(".drop-toggle").forEach((el) => {
      el.addEventListener("hover", (e) => {
        console.log("hover");
        // close all other dropdowns
        e.preventDefault();
        e.stopImmediatePropagation();
        document.querySelectorAll(".dropdown-menu").forEach((el) => {
          el.classList.remove("show");
        });
        e.target.nextElementSibling.classList.add("show");
      });
    });

    $[if `process.env.BATCH_DOWNLOAD === "true"`]$
    updateMultiselect((localStorage.getItem("multiSelectMode") ?? false) === "true");
    $[end]$

    $[if `process.env.LAYOUT === "popup"`]$
    (() => {
      const copyBtn = document.querySelector('#file-popup-copy');
      if (copyBtn && copyBtn.dataset.dbBoundClick !== '1') {
        copyBtn.dataset.dbBoundClick = '1';
        copyBtn.addEventListener('click', async (e) => {
          e.preventDefault();
          await copyPreviewToClipboard();
        });
      }
    })();

    document.querySelectorAll('.db-file').forEach((item) => {
      // skip folders
      if (item.getAttribute('data-file-isdir') === '1') {
        return;
      }
      item.addEventListener('click', async (e) => {
        // If multiselect mode is on, keep existing multiselect behavior.
        if ((localStorage.getItem("multiSelectMode") ?? "false") === "true") return;
        e.preventDefault();

        // only do this on reload click button refreshFileinfo()
        // const data = await fetch(item.href + "?info").then((res) => res.json());
        // alert(JSON.stringify(data, null, 2));

        await setFileinfo({
          name: item.getAttribute('data-file-name'),
          url: item.href
        });
      })
    });
    document.querySelector('#file-popup').addEventListener('click', (e) => {
      if (e.target === document.querySelector('#file-popup')) {
        document.querySelector('#file-popup').classList.remove("d-block");
        document.querySelector('#file-popup').classList.remove("show");
        document.querySelector('#file-popup').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
      }
    });
    document.querySelector('#file-popup-x').addEventListener('click', (e) => {
      document.querySelector('#file-popup').classList.remove("d-block");
      document.querySelector('#file-popup').classList.remove("show");
      document.querySelector('#file-popup').setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    });
    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      const popup = document.querySelector('#file-popup.show');
      if (!popup) return;
      popup.classList.remove('d-block', 'show');
      popup.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    });
    $[end]$

    $[if `process.env.SEARCH === "true"`]$
    document.querySelector('#search').addEventListener('input', search);
    document.querySelector('#searchengine').addEventListener('change', search);
    $[end]$

    document.querySelector('#name').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:name", localStorage.getItem("sort:order:name") === "asc" ? "desc" : "asc");
      sort('name', localStorage.getItem("sort:order:name") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:dl");
      localStorage.removeItem("sort:order:size");
      localStorage.removeItem("sort:order:mod");
    });

    $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
    document.querySelector('#dl').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:dl", localStorage.getItem("sort:order:dl") === "asc" ? "desc" : "asc");
      sort('dl', localStorage.getItem("sort:order:dl") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:name");
      localStorage.removeItem("sort:order:size");
      localStorage.removeItem("sort:order:mod");
    });
    $[end]$

    document.querySelector('#size').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:size", localStorage.getItem("sort:order:size") === "asc" ? "desc" : "asc");
      sort('size', localStorage.getItem("sort:order:size") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:name");
      localStorage.removeItem("sort:order:dl");
      localStorage.removeItem("sort:order:mod");
    });

    document.querySelector('#mod').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:mod", localStorage.getItem("sort:order:mod") === "asc" ? "desc" : "asc");
      sort('mod', localStorage.getItem("sort:order:mod") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:name");
      localStorage.removeItem("sort:order:dl");
      localStorage.removeItem("sort:order:size");
    });
  </script>
  $[if `process.env.ICONS !== "false"`]$
  <script data-turbo-eval="false" src="https://cdn.jsdelivr.net/npm/file-icons-js@1/dist/file-icons.min.js"></script>
  <script>
    var icons = window.FileIcons;
    document.querySelectorAll(".file-icon-placeholder").forEach(function(element) {
      element.classList = ("icon " + icons.getClassWithColor(element.getAttribute("filename"))).replace("null","binary-icon")
      element.innerHTML = ""
    })
  </script>
  $[end]$  
  <script data-turbo-eval="false">
    document.addEventListener('click', (event) => {
      const dismiss = event.target.closest('[data-toast-dismiss]');
      if (!dismiss) return;
      const toast = dismiss.closest('.toast');
      window.clearTimeout(toast?._hideTimer);
      toast?.classList.remove('show');
    });
  </script>

  $[if `process.env.JS_URL_ONCE !== undefined`]$
  <script data-turbo-eval="false" src="${{`process.env.JS_URL`}}$"></script>
  $[end]$
  $[if `process.env.JS_URL !== undefined`]$
  <script src="${{`process.env.JS_URL`}}$"></script>
  $[end]$
  $[if `process.env.JS !== undefined`]$
  <script>${{`process.env.JS`}}$</script>
  $[end]$
