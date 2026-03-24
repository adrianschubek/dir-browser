# todo
- csv render as table
- button to make preview dialog fullscreen 

- large batch download: zip error. this fix idk if work it doesnt stream in brwoser anymore 4.4.1

- copy buttons "Copy cURL (bash)" and "Copy cURL (CMD/PowerShell)". as dropdown. it copies the download command to clipboard. Use origin domain from current page.
- for copy folder: new api <folderUrl>?batchdl

- maybe use react/shadcn for frontend



<!-- Refactor the entire old codebase (remove php, nginx, redis) to use the new NextJs and Bun server. Implement all API routes in the Bun server and connect them to the NextJs frontend. Implement UI using shadcn/ui components (see components folder). Make it look professional. Don't use server actions (use the Bun server).

- bun zip libraries: https://github.com/gildas-lormeau/zip.js use @zip-js/zip-js with TransformStream 

- bun markdown: https://bun.com/docs/runtime/markdown Bun.markdown.html(text, options) -> string

- remove redis and use an in-memory sqlite database instead using Bun's sqlite API https://bun.com/docs/runtime/sqlite . Periodically serialize() it to disk. On startup load it back into memory with deserialize(). 

- Adjust Dockerfile as needed. -->

- batch download button missing
- try to preview application/octet-stream files as text (truncate if too large)
- syntax highlighting
code: (children, meta) => {
    const lang = meta?.language ?? "";
    return `<pre><code class="language-${lang}">${children}</code></pre>`;
  },