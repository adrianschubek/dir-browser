import { ZipWriter } from "@zip.js/zip.js"
import { Database } from "bun:sqlite"
import fs from "node:fs"
import fsp from "node:fs/promises"
import path from "node:path"

type AccessConfig = {
  password_hash?: string
  password_raw?: string
  hidden?: boolean
  inherit?: boolean
}

type AccessState = {
  hidden: boolean
  requiresPassword: boolean
  authorized: boolean
  passwordHash?: string
  passwordRaw?: string
}

type AccessKeySource = "header" | "post" | "cookie" | "query" | null

type AccessKey = {
  value: string | null
  source: AccessKeySource
}

type DirectoryItem = {
  url: string
  name: string
  type: "file" | "dir"
  size: number
  modified: string
  downloads: number
  auth_required?: boolean
  auth_locked?: boolean
  metadata?: {
    description?: string
    labels?: string[]
    hash_required?: boolean
    hidden?: boolean
  }
}

type SearchResult = {
  url: string
  name: string
  is_dir: boolean
  auth_required: boolean
  auth_locked: boolean
}

type ParsedBody = {
  key?: string
  downloadBatch?: string[]
}

const REPO_ROOT = path.resolve(import.meta.dir, "../..")
const DEFAULT_PUBLIC_FOLDER = path.resolve(REPO_ROOT, "examples")
const DEFAULT_COUNTER_SNAPSHOT = path.resolve(REPO_ROOT, ".data", "counters.sqlite.bin")

function parseBoolean(value: string | undefined, fallback: boolean) {
  if (value == null) return fallback
  return value.toLowerCase() === "true"
}

function parseNumber(value: string | undefined, fallback: number) {
  const parsed = Number(value)
  if (!Number.isFinite(parsed)) return fallback
  return parsed
}

const config = {
  title: process.env.TITLE ?? "dir-browser",
  publicFolder: path.resolve(process.env.PUBLIC_FOLDER ?? DEFAULT_PUBLIC_FOLDER),
  basePath: process.env.BASE_PATH ?? "",
  apiEnabled: parseBoolean(process.env.API, true),
  metadataEnabled: parseBoolean(process.env.METADATA, true),
  searchEnabled: parseBoolean(process.env.SEARCH, true),
  readmeRender: parseBoolean(process.env.README_RENDER, true),
  readmeName: (process.env.README_NAME ?? "readme.md;readme.txt;readme.html;readme")
    .split(";")
    .map((name) => name.trim().toLowerCase())
    .filter(Boolean),
  downloadCounter: parseBoolean(process.env.DOWNLOAD_COUNTER, true),
  paginationPerPage: parseNumber(process.env.PAGINATION_PER_PAGE, 100),
  authCookieLifetime: parseNumber(process.env.AUTH_COOKIE_LIFETIME, 60 * 60 * 24 * 30),
  authCookieHttpOnly: parseBoolean(process.env.AUTH_COOKIE_HTTPONLY, true),
  hashEnabled: parseBoolean(process.env.HASH, true),
  hashAlgo: process.env.HASH_ALGO ?? "sha256",
  hashRequired: parseBoolean(process.env.HASH_REQUIRED, false),
  hashMaxFileSizeMb: parseNumber(process.env.HASH_MAX_FILE_SIZE_MB, 100),
  passwordUser: process.env.PASSWORD_USER,
  passwordRaw: process.env.PASSWORD_RAW,
  passwordHash: process.env.PASSWORD_HASH,
  corsAllowAnyOrigin: parseBoolean(process.env.CORS_ALLOW_ANY_ORIGIN, true),
  searchEngines: (process.env.SEARCH_ENGINE ?? "s,g").split(",").map((value) => value.trim()),
  searchMaxDepth: parseNumber(process.env.SEARCH_MAX_DEPTH, 25),
  searchMaxResults: parseNumber(process.env.SEARCH_MAX_RESULTS, 100),
  batchEnabled: parseBoolean(process.env.BATCH_DOWNLOAD, true),
  batchMaxTotalSizeMb: parseNumber(process.env.BATCH_MAX_TOTAL_SIZE, 500000),
  batchMaxFileSizeMb: parseNumber(process.env.BATCH_MAX_FILE_SIZE, 500000),
  batchCompressAlgo: (process.env.BATCH_ZIP_COMPRESS_ALGO ?? "STORE").toUpperCase(),
  ignorePatterns: (process.env.IGNORE ?? "").split(";").filter(Boolean),
  nextHost: process.env.NEXT_HOST ?? "127.0.0.1",
  nextPort: parseNumber(process.env.NEXT_PORT, 3000),
  port: parseNumber(process.env.PORT, 8080),
  counterSnapshotPath: path.resolve(process.env.COUNTER_SNAPSHOT_PATH ?? DEFAULT_COUNTER_SNAPSHOT),
  counterFlushIntervalMs: parseNumber(process.env.COUNTER_FLUSH_INTERVAL_MS, 10_000),
  uiBasePath: process.env.UI_BASE_PATH ?? "/ui",
}

if (!fs.existsSync(config.publicFolder)) {
  throw new Error(`PUBLIC_FOLDER does not exist: ${config.publicFolder}`)
}

function normalizeUserPath(inputPath: string): string {
  let decoded = inputPath
  try {
    decoded = decodeURIComponent(inputPath)
  } catch {
    decoded = inputPath
  }
  if (!decoded.startsWith("/")) decoded = `/${decoded}`
  decoded = decoded.replace(/\\/g, "/")
  decoded = decoded.replace(/\/+/g, "/")
  return decoded || "/"
}

function toPosixPath(inputPath: string): string {
  return inputPath.split(path.sep).join("/")
}

function userPathFromLocal(localPath: string): string {
  const relative = toPosixPath(path.relative(config.publicFolder, localPath))
  if (!relative || relative === ".") return "/"
  return `/${relative}`
}

function localPathFromUserPath(userPath: string): string {
  const normalized = normalizeUserPath(userPath)
  return path.resolve(config.publicFolder, `.${normalized}`)
}

function isWithinPublicRoot(localPath: string): boolean {
  const relative = path.relative(config.publicFolder, localPath)
  return relative === "" || (!relative.startsWith("..") && !path.isAbsolute(relative))
}

async function resolveExistingLocalPath(userPath: string): Promise<string | null> {
  const candidate = localPathFromUserPath(userPath)
  if (!isWithinPublicRoot(candidate)) return null
  try {
    await fsp.access(candidate)
  } catch {
    return null
  }

  try {
    const resolved = await fsp.realpath(candidate)
    if (!isWithinPublicRoot(resolved)) return null
    return resolved
  } catch {
    return null
  }
}

function shouldIgnorePath(localPath: string): boolean {
  if (config.ignorePatterns.length === 0) return false
  for (const pattern of config.ignorePatterns) {
    try {
      const regex = new RegExp(pattern, "im")
      if (regex.test(localPath)) return true
    } catch {
      continue
    }
  }
  return false
}

function isAccessConfigPath(localPath: string): boolean {
  return path.basename(localPath) === ".access.json"
}

function isDbMetaSidecarPath(localPath: string): boolean {
  const name = path.basename(localPath)
  return name.includes(".dbmeta.")
}

function parseCookies(cookieHeader: string | null): Record<string, string> {
  if (!cookieHeader) return {}
  return cookieHeader.split(";").reduce<Record<string, string>>((acc, part) => {
    const [rawKey, ...rawValue] = part.trim().split("=")
    if (!rawKey) return acc
    acc[rawKey] = decodeURIComponent(rawValue.join("="))
    return acc
  }, {})
}

function buildCookie(name: string, value: string, maxAgeSeconds: number) {
  const secure = (process.env.NODE_ENV ?? "").toLowerCase() === "production"
  const cookieParts = [
    `${name}=${encodeURIComponent(value)}`,
    `Path=${config.basePath || "/"}`,
    `Max-Age=${maxAgeSeconds}`,
    "SameSite=Lax",
  ]
  if (secure) cookieParts.push("Secure")
  if (config.authCookieHttpOnly) cookieParts.push("HttpOnly")
  return cookieParts.join("; ")
}

async function verifyPasswordCandidate(candidate: string, raw?: string, hash?: string): Promise<boolean> {
  if (hash) {
    try {
      return await Bun.password.verify(candidate, hash)
    } catch {
      return false
    }
  }
  if (raw != null) {
    return raw === candidate
  }
  return false
}

function parseAuthorizationHeader(req: Request): { username: string; password: string } | null {
  const header = req.headers.get("authorization")
  if (!header || !header.toLowerCase().startsWith("basic ")) return null
  try {
    const decoded = Buffer.from(header.slice(6), "base64").toString("utf8")
    const separatorIndex = decoded.indexOf(":")
    if (separatorIndex === -1) return null
    return {
      username: decoded.slice(0, separatorIndex),
      password: decoded.slice(separatorIndex + 1),
    }
  } catch {
    return null
  }
}

async function requireGlobalAuth(req: Request): Promise<Response | null> {
  if (!config.passwordUser || (!config.passwordRaw && !config.passwordHash)) {
    return null
  }

  const credentials = parseAuthorizationHeader(req)
  const validUser = credentials?.username === config.passwordUser
  const validPassword = credentials
    ? await verifyPasswordCandidate(credentials.password, config.passwordRaw, config.passwordHash)
    : false

  if (!validUser || !validPassword) {
    return new Response("Authentication required", {
      status: 401,
      headers: {
        "WWW-Authenticate": 'Basic realm="dir-browser"',
      },
    })
  }

  return null
}

const accessConfigCache = new Map<string, AccessConfig | null>()
const effectiveAccessCache = new Map<string, AccessState>()
const metadataCache = new Map<string, DirectoryItem["metadata"] | null>()

async function readJsonFile(filePath: string): Promise<unknown | null> {
  try {
    const content = await fsp.readFile(filePath, "utf8")
    return JSON.parse(content)
  } catch {
    return null
  }
}

async function readAccessConfig(localDirPath: string): Promise<AccessConfig | null> {
  if (accessConfigCache.has(localDirPath)) {
    return accessConfigCache.get(localDirPath) ?? null
  }

  const accessPath = path.join(localDirPath, ".access.json")
  const raw = await readJsonFile(accessPath)
  if (!raw || typeof raw !== "object") {
    accessConfigCache.set(localDirPath, null)
    return null
  }

  const source = raw as Record<string, unknown>
  const configValue: AccessConfig = {}
  if (typeof source.password_hash === "string" && source.password_hash.length > 0) {
    configValue.password_hash = source.password_hash
  }
  if (typeof source.password_raw === "string" && source.password_raw.length > 0) {
    configValue.password_raw = source.password_raw
  }
  if (typeof source.hidden === "boolean") {
    configValue.hidden = source.hidden
  }
  if (typeof source.inherit === "boolean") {
    configValue.inherit = source.inherit
  }

  accessConfigCache.set(localDirPath, configValue)
  return configValue
}

async function effectiveAccessForDir(localDirPath: string): Promise<AccessState> {
  if (effectiveAccessCache.has(localDirPath)) {
    return effectiveAccessCache.get(localDirPath) as AccessState
  }

  const chain: string[] = []
  let cursor = localDirPath
  while (true) {
    chain.push(cursor)
    if (path.resolve(cursor) === path.resolve(config.publicFolder)) break
    const parent = path.dirname(cursor)
    if (parent === cursor || !isWithinPublicRoot(parent)) break
    cursor = parent
  }
  chain.reverse()

  const effective: AccessState = {
    hidden: false,
    requiresPassword: false,
    authorized: true,
  }

  for (const chainDir of chain) {
    const currentConfig = await readAccessConfig(chainDir)
    if (!currentConfig) continue
    const applies = chainDir === localDirPath || currentConfig.inherit !== false
    if (!applies) continue

    if (typeof currentConfig.hidden === "boolean") {
      effective.hidden = currentConfig.hidden
    }

    if (currentConfig.password_hash) {
      effective.passwordHash = currentConfig.password_hash
      delete effective.passwordRaw
    } else if (currentConfig.password_raw) {
      effective.passwordRaw = currentConfig.password_raw
      delete effective.passwordHash
    }
  }

  effective.requiresPassword = Boolean(effective.passwordHash || effective.passwordRaw)
  effectiveAccessCache.set(localDirPath, effective)
  return effective
}

async function resolveRequestBody(req: Request): Promise<ParsedBody> {
  if (req.method !== "POST") return {}
  const contentType = req.headers.get("content-type") ?? ""

  if (contentType.includes("application/json")) {
    try {
      const parsed = (await req.clone().json()) as Record<string, unknown>
      const batch = parsed.download_batch
      return {
        key: typeof parsed.key === "string" ? parsed.key : undefined,
        downloadBatch: Array.isArray(batch) ? batch.filter((entry): entry is string => typeof entry === "string") : undefined,
      }
    } catch {
      return {}
    }
  }

  try {
    const formData = await req.clone().formData()
    const fieldBatch = [
      ...formData.getAll("download_batch[]"),
      ...formData.getAll("download_batch"),
    ]

    return {
      key: typeof formData.get("key") === "string" ? (formData.get("key") as string) : undefined,
      downloadBatch: fieldBatch.filter((entry): entry is string => typeof entry === "string"),
    }
  } catch {
    return {}
  }
}

function extractAccessKey(req: Request, url: URL, parsedBody: ParsedBody): AccessKey {
  const fromHeader = req.headers.get("x-key")
  if (fromHeader) return { value: fromHeader, source: "header" }

  if (parsedBody.key) return { value: parsedBody.key, source: "post" }

  const cookies = parseCookies(req.headers.get("cookie"))
  if (cookies.dir_browser_key) return { value: cookies.dir_browser_key, source: "cookie" }

  const keyQuery = url.searchParams.get("key")
  if (keyQuery) return { value: keyQuery, source: "query" }

  return { value: null, source: null }
}

async function accessStatusForLocalPath(localPath: string, key: string | null): Promise<AccessState> {
  const localDir = (await fsp.stat(localPath)).isDirectory() ? localPath : path.dirname(localPath)
  const effective = { ...(await effectiveAccessForDir(localDir)) }
  let authorized = true
  if (effective.requiresPassword) {
    if (!key) {
      authorized = false
    } else {
      authorized = await verifyPasswordCandidate(key, effective.passwordRaw, effective.passwordHash)
    }
  }

  effective.authorized = authorized
  return effective
}

async function readMetadata(localPath: string): Promise<DirectoryItem["metadata"] | null> {
  if (!config.metadataEnabled) return null
  if (metadataCache.has(localPath)) return metadataCache.get(localPath) ?? null

  const metadataPath = `${localPath}.dbmeta.json`
  const json = await readJsonFile(metadataPath)
  if (!json || typeof json !== "object") {
    metadataCache.set(localPath, null)
    return null
  }

  const source = json as Record<string, unknown>
  const result: DirectoryItem["metadata"] = {}

  if (typeof source.description === "string") {
    result.description = source.description
  }
  if (Array.isArray(source.labels)) {
    result.labels = source.labels.filter((value): value is string => typeof value === "string")
  }
  if (typeof source.hash_required === "boolean") {
    result.hash_required = source.hash_required
  }
  if (source.hidden === true) {
    result.hidden = true
  }

  metadataCache.set(localPath, result)
  return result
}

type AvailableResult = {
  localPath: string
  access: AccessState
  metadata: DirectoryItem["metadata"] | null
}

async function availablePath(userPath: string, key: string | null, includeProtected = false): Promise<AvailableResult | null> {
  const localPath = await resolveExistingLocalPath(userPath)
  if (!localPath) return null
  if (shouldIgnorePath(localPath)) return null
  if (isAccessConfigPath(localPath)) return null
  if (config.metadataEnabled && isDbMetaSidecarPath(localPath)) return null

  const access = await accessStatusForLocalPath(localPath, key)
  if (access.hidden) return null
  if (!includeProtected && access.requiresPassword && !access.authorized) return null

  const metadata = await readMetadata(localPath)
  if (metadata?.hidden === true) return null

  return {
    localPath,
    access,
    metadata,
  }
}

function sanitizeHeaderValue(value: string) {
  return value.replace(/[\r\n]/g, " ")
}

class CounterStore {
  private db: Database
  private readonly getStmt
  private readonly setStmt
  private readonly insertStmt
  private readonly incrementStmt
  private readonly deleteStmt
  private readonly txIncrementMany
  private readonly flushTimer: ReturnType<typeof setInterval>
  private isFlushing = false

  constructor(private readonly snapshotPath: string, flushIntervalMs: number) {
    this.db = this.loadDatabase()
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS counters (
        path TEXT PRIMARY KEY,
        count INTEGER NOT NULL DEFAULT 0
      );
    `)

    this.getStmt = this.db.prepare<{ count: number }, [string]>("SELECT count FROM counters WHERE path = ?")
    this.setStmt = this.db.prepare("INSERT INTO counters(path, count) VALUES(?, ?) ON CONFLICT(path) DO UPDATE SET count=excluded.count")
    this.insertStmt = this.db.prepare("INSERT OR IGNORE INTO counters(path, count) VALUES(?, 0)")
    this.incrementStmt = this.db.prepare("UPDATE counters SET count = count + 1 WHERE path = ?")
    this.deleteStmt = this.db.prepare("DELETE FROM counters WHERE path = ?")
    this.txIncrementMany = this.db.transaction((paths: string[]) => {
      for (const itemPath of paths) {
        this.insertStmt.run(itemPath)
        this.incrementStmt.run(itemPath)
      }
    })

    this.flushTimer = setInterval(() => {
      void this.flushToDisk()
    }, flushIntervalMs)
  }

  private loadDatabase(): Database {
    try {
      if (fs.existsSync(this.snapshotPath)) {
        const file = Bun.file(this.snapshotPath)
        if (file.size > 0) {
          return Database.deserialize(fs.readFileSync(this.snapshotPath), { strict: true })
        }
      }
    } catch (error) {
      console.error("[counter-store] failed to load snapshot, using empty in-memory db", error)
    }

    return Database.open(":memory:", { strict: true })
  }

  get(pathValue: string): number {
    if (!config.downloadCounter) return 0
    const row = this.getStmt.get(pathValue)
    return row?.count ?? 0
  }

  getMany(paths: string[]): Record<string, number> {
    const output: Record<string, number> = {}
    if (!config.downloadCounter || paths.length === 0) return output
    for (const itemPath of paths) {
      output[itemPath] = this.get(itemPath)
    }
    return output
  }

  increment(pathValue: string) {
    if (!config.downloadCounter) return
    this.insertStmt.run(pathValue)
    this.incrementStmt.run(pathValue)
  }

  incrementMany(paths: string[]) {
    if (!config.downloadCounter || paths.length === 0) return
    this.txIncrementMany(paths)
  }

  reset(pathValue: string) {
    this.deleteStmt.run(pathValue)
  }

  async flushToDisk() {
    if (this.isFlushing) return
    this.isFlushing = true
    try {
      await fsp.mkdir(path.dirname(this.snapshotPath), { recursive: true })
      const bytes = this.db.serialize()
      await Bun.write(this.snapshotPath, bytes)
    } catch (error) {
      console.error("[counter-store] failed to flush snapshot", error)
    } finally {
      this.isFlushing = false
    }
  }

  async shutdown() {
    clearInterval(this.flushTimer)
    await this.flushToDisk()
    this.db.close(false)
  }
}

const counterStore = new CounterStore(config.counterSnapshotPath, config.counterFlushIntervalMs)

const shutdownSignals: NodeJS.Signals[] = ["SIGINT", "SIGTERM"]
for (const signal of shutdownSignals) {
  process.on(signal, () => {
    void counterStore.shutdown().finally(() => process.exit(0))
  })
}

function withCorsHeaders(headers: Headers) {
  if (config.corsAllowAnyOrigin) {
    headers.set("Access-Control-Allow-Origin", "*")
    headers.set("Access-Control-Allow-Headers", "Authorization, Content-Type, X-Key")
    headers.set("Access-Control-Allow-Methods", "GET,POST,OPTIONS")
  }
}

function jsonResponse(data: unknown, init?: ResponseInit): Response {
  const headers = new Headers(init?.headers)
  headers.set("Content-Type", "application/json")
  withCorsHeaders(headers)
  return new Response(JSON.stringify(data), {
    ...init,
    headers,
  })
}

function textResponse(message: string, status = 200, headersInit?: HeadersInit): Response {
  const headers = new Headers(headersInit)
  withCorsHeaders(headers)
  return new Response(message, { status, headers })
}

function stripQueryParam(inputUrl: URL, key: string): string {
  const nextUrl = new URL(inputUrl.toString())
  nextUrl.searchParams.delete(key)
  const query = nextUrl.searchParams.toString()
  return `${nextUrl.pathname}${query ? `?${query}` : ""}`
}

function hashFileAllowed(fileSizeBytes: number) {
  if (!config.hashEnabled) return false
  return fileSizeBytes <= config.hashMaxFileSizeMb * 1024 * 1024
}

function inferPreviewKind(mimeType: string, extension: string): "text" | "json" | "markdown" | "image" | "pdf" | "video" | "audio" | "none" {
  if (mimeType.startsWith("image/")) return "image"
  if (mimeType === "application/pdf" || extension === "pdf") return "pdf"
  if (mimeType.startsWith("video/")) return "video"
  if (mimeType.startsWith("audio/") || ["mp3", "m4a", "aac", "wav", "ogg", "oga", "opus", "flac"].includes(extension)) return "audio"
  if (mimeType === "application/json" || extension === "json") return "json"
  if (extension === "md") return "markdown"
  if (mimeType.startsWith("text/") || ["txt", "csv", "log", "yaml", "yml", "ini", "xml", "html", "css", "js", "ts", "php"].includes(extension)) {
    return "text"
  }
  return "none"
}

async function readDirectoryReadmeHtml(localDirPath: string, files: DirectoryItem[]): Promise<string | null> {
  if (!config.readmeRender) return null

  const metaReadmePath = path.join(localDirPath, ".dbmeta.md")
  try {
    await fsp.access(metaReadmePath)
    const text = await fsp.readFile(metaReadmePath, "utf8")
    return Bun.markdown.html(text)
  } catch {
    // continue
  }

  const readmeCandidate = files.find((file) => config.readmeName.includes(file.name.toLowerCase()))
  if (!readmeCandidate) return null

  const localReadmePath = localPathFromUserPath(readmeCandidate.url)
  try {
    const text = await fsp.readFile(localReadmePath, "utf8")
    return Bun.markdown.html(text)
  } catch {
    return null
  }
}

async function listDirectory(userPath: string, localPath: string, key: string | null): Promise<{ items: DirectoryItem[]; readmeHtml: string | null }> {
  const entries = await fsp.readdir(localPath, { withFileTypes: true })
  const folders: DirectoryItem[] = []
  const files: DirectoryItem[] = []

  for (const entry of entries) {
    const localEntryPathRaw = path.join(localPath, entry.name)
    let localEntryPath: string
    try {
      localEntryPath = await fsp.realpath(localEntryPathRaw)
    } catch {
      continue
    }

    if (!isWithinPublicRoot(localEntryPath)) continue
    if (shouldIgnorePath(localEntryPath)) continue
    if (entry.name === ".access.json") continue
    if (config.metadataEnabled && entry.name.includes(".dbmeta.")) continue

    const available = await availablePath(userPathFromLocal(localEntryPath), key)
    if (!available) continue

    const stat = await fsp.stat(localEntryPath)
    const item: DirectoryItem = {
      url: userPathFromLocal(localEntryPath),
      name: entry.name,
      type: stat.isDirectory() ? "dir" : "file",
      size: stat.isDirectory() ? 0 : stat.size,
      modified: new Date(stat.mtimeMs).toISOString(),
      downloads: 0,
    }

    if (available.metadata) {
      item.metadata = {
        description: available.metadata.description,
        labels: available.metadata.labels,
        hash_required: available.metadata.hash_required,
      }
    }

    if (item.type === "dir") {
      item.auth_required = available.access.requiresPassword
      item.auth_locked = available.access.requiresPassword && !available.access.authorized
      folders.push(item)
    } else {
      files.push(item)
    }
  }

  const naturalCompare = (left: DirectoryItem, right: DirectoryItem) =>
    left.name.localeCompare(right.name, undefined, { numeric: true, sensitivity: "base" })

  folders.sort(naturalCompare)
  files.sort(naturalCompare)

  const sorted = [...folders, ...files]
  const downloadMap = counterStore.getMany(sorted.map((item) => item.url))
  for (const item of sorted) {
    item.downloads = downloadMap[item.url] ?? 0
  }

  const readmeHtml = await readDirectoryReadmeHtml(localPath, files)

  return {
    items: sorted,
    readmeHtml,
  }
}

async function runSearch(userPath: string, localPath: string, query: string, engine: string, key: string | null) {
  const results: SearchResult[] = []

  if (!config.searchEngines.includes(engine)) {
    return { error: "Invalid search engine", status: 400 as const }
  }

  const normalizedQuery = query.trim()
  if (!normalizedQuery) {
    return { error: "Empty search query", status: 400 as const }
  }

  async function maybePushResult(resultPath: string) {
    if (results.length >= config.searchMaxResults) return
    const availability = await availablePath(userPathFromLocal(resultPath), key, true)
    if (!availability) return

    const stat = await fsp.stat(resultPath)
    const relativeName = toPosixPath(path.relative(localPath, resultPath))
    const authRequired = availability.access.requiresPassword
    const authLocked = authRequired && !availability.access.authorized

    results.push({
      url: userPathFromLocal(resultPath),
      name: relativeName,
      is_dir: stat.isDirectory(),
      auth_required: authRequired,
      auth_locked: authLocked,
    })
  }

  if (engine === "g") {
    const glob = new Bun.Glob(normalizedQuery)
    for await (const match of glob.scan({ cwd: localPath })) {
      if (results.length >= config.searchMaxResults) break
      const resolved = await resolveExistingLocalPath(userPathFromLocal(path.join(localPath, match)))
      if (!resolved) continue
      await maybePushResult(resolved)
    }
  } else {
    const regex = engine === "r" ? new RegExp(normalizedQuery) : null
    const lowered = normalizedQuery.toLowerCase()

    async function walk(currentPath: string, depth: number) {
      if (results.length >= config.searchMaxResults) return
      if (depth > config.searchMaxDepth) return

      const entries = await fsp.readdir(currentPath, { withFileTypes: true })
      for (const entry of entries) {
        if (results.length >= config.searchMaxResults) return
        const childPath = path.join(currentPath, entry.name)
        let childReal: string
        try {
          childReal = await fsp.realpath(childPath)
        } catch {
          continue
        }
        if (!isWithinPublicRoot(childReal)) continue

        const childNameMatch = engine === "s"
          ? entry.name.toLowerCase().includes(lowered)
          : Boolean(regex?.test(childReal))

        if (childNameMatch) {
          await maybePushResult(childReal)
        }

        if (entry.isDirectory()) {
          await walk(childReal, depth + 1)
        }
      }
    }

    await walk(localPath, 0)
  }

  return {
    total: results.length,
    truncated: results.length >= config.searchMaxResults,
    base_folder: userPath,
    results,
  }
}

async function getDeepUrlsFromArray(urls: string[], key: string | null): Promise<string[]> {
  const files: string[] = []

  async function recurse(userPath: string) {
    const available = await availablePath(userPath, key)
    if (!available) return

    const stat = await fsp.stat(available.localPath)
    if (stat.isDirectory()) {
      const entries = await fsp.readdir(available.localPath)
      for (const entry of entries) {
        await recurse(path.posix.join(userPathFromLocal(available.localPath), entry))
      }
      return
    }

    files.push(userPathFromLocal(available.localPath))
  }

  for (const inputUrl of urls) {
    await recurse(normalizeUserPath(inputUrl))
  }

  return files
}

function extractBatchUrls(parsedBody: ParsedBody): string[] {
  const batch = parsedBody.downloadBatch ?? []
  return batch.map((value) => normalizeUserPath(value))
}

async function createZipStreamResponse(filesToZip: Array<{ archiveName: string; localPath: string; size: number }>): Promise<Response> {
  const stream = new TransformStream<Uint8Array, Uint8Array>()
  const zipWriter = new ZipWriter(stream.writable as WritableStream)
  const noCompression = config.batchCompressAlgo === "STORE"

  void (async () => {
    try {
      for (const file of filesToZip) {
        const blob = Bun.file(file.localPath)
        await zipWriter.add(file.archiveName, blob.stream(), {
          level: noCompression ? 0 : 6,
        })
      }
      await zipWriter.close()
    } catch (error) {
      console.error("[zip] stream failed", error)
      await stream.writable.abort(error)
    }
  })()

  const headers = new Headers({
    "Content-Type": "application/zip",
    "Content-Disposition": `attachment; filename=\"${Date.now().toString(16)}.zip\"`,
    "Cache-Control": "no-store, no-cache, must-revalidate, max-age=0",
    Pragma: "no-cache",
    Expires: "0",
  })
  withCorsHeaders(headers)

  return new Response(stream.readable, { headers })
}

function getMimeType(localPath: string): string {
  return Bun.file(localPath).type || "application/octet-stream"
}

async function getHash(localPath: string): Promise<string | null> {
  try {
    const hash = new Bun.CryptoHasher(config.hashAlgo as Bun.SupportedCryptoAlgorithms)
    hash.update(await Bun.file(localPath).arrayBuffer())
    return hash.digest("hex")
  } catch {
    return null
  }
}

function shouldTreatAsFsRequest(url: URL, req: Request): boolean {
  if (url.searchParams.has("ls")) return true
  if (url.searchParams.has("info")) return true
  if (url.searchParams.has("preview")) return true
  if (url.searchParams.has("raw")) return true
  if (url.searchParams.has("q")) return true
  if (url.searchParams.has("logout")) return true
  if (url.searchParams.has("hash")) return true
  if (url.searchParams.has("key")) return true
  if (req.method === "POST") return true
  return false
}

async function handleDirectoryRequest(args: {
  req: Request
  url: URL
  userPath: string
  localPath: string
  key: AccessKey
  parsedBody: ParsedBody
  isLegacyPath: boolean
}): Promise<Response> {
  const { req, url, userPath, localPath, key, parsedBody, isLegacyPath } = args

  if (url.searchParams.has("logout")) {
    const headers = new Headers({
      Location: stripQueryParam(url, "logout"),
      "Set-Cookie": buildCookie("dir_browser_key", "", 0),
    })
    withCorsHeaders(headers)
    return new Response(null, { status: 303, headers })
  }

  const access = await accessStatusForLocalPath(localPath, key.value)
  if (access.hidden) {
    return textResponse("Not found", 404)
  }

  if (access.requiresPassword && !access.authorized) {
    return textResponse("Authentication required", 401)
  }

  if ((key.source === "post" || key.source === "query") && key.value) {
    const headers = new Headers({
      "Set-Cookie": buildCookie("dir_browser_key", key.value, config.authCookieLifetime),
    })
    withCorsHeaders(headers)

    if (isLegacyPath) {
      headers.set("Location", stripQueryParam(url, "key"))
      return new Response(null, { status: 303, headers })
    }
  }

  if (config.searchEnabled && url.searchParams.has("q") && url.searchParams.has("e")) {
    const searchResult = await runSearch(
      userPath,
      localPath,
      url.searchParams.get("q") ?? "",
      url.searchParams.get("e") ?? "",
      key.value
    )
    if ("error" in searchResult) {
      return textResponse(searchResult.error ?? "Search failed", searchResult.status)
    }
    return jsonResponse(searchResult)
  }

  if (config.batchEnabled && req.method === "POST") {
    const batchUrls = extractBatchUrls(parsedBody)
    if (batchUrls.length > 0) {
      const flattened = await getDeepUrlsFromArray(batchUrls, key.value)
      const unique = Array.from(new Set(flattened))

      if (unique.length === 0) {
        return textResponse("Batch download error: no eligible files selected", 400)
      }

      const filesToZip: Array<{ archiveName: string; localPath: string; size: number; userPath: string }> = []
      let totalSize = 0

      for (const selectedPath of unique) {
        const available = await availablePath(selectedPath, key.value)
        if (!available) {
          return textResponse(`Batch download error: invalid file ${selectedPath}`, 400)
        }

        const stat = await fsp.stat(available.localPath)
        if (!stat.isFile()) continue

        if (stat.size > config.batchMaxFileSizeMb * 1024 * 1024) {
          return textResponse(`Batch download error: file exceeds per-file size limit: ${selectedPath}`, 400)
        }

        totalSize += stat.size
        if (totalSize > config.batchMaxTotalSizeMb * 1024 * 1024) {
          return textResponse("Batch download error: total size exceeds configured limit", 400)
        }

        filesToZip.push({
          archiveName: userPathFromLocal(available.localPath).replace(/^\//, ""),
          localPath: available.localPath,
          size: stat.size,
          userPath: userPathFromLocal(available.localPath),
        })
      }

      counterStore.incrementMany(filesToZip.map((item) => item.userPath))
      return createZipStreamResponse(filesToZip)
    }
  }

  if (url.searchParams.has("ls") || !isLegacyPath) {
    if (!config.apiEnabled && url.searchParams.has("ls")) {
      return textResponse("API disabled", 404)
    }
    const listResult = await listDirectory(userPath, localPath, key.value)
    return jsonResponse({
      path: userPath,
      readme_html: listResult.readmeHtml,
      pagination_per_page: config.paginationPerPage,
      items: listResult.items,
    })
  }

  const location = `${config.uiBasePath}${userPath === "/" ? "" : userPath}`
  return Response.redirect(location, 302)
}

async function handleFileRequest(args: {
  req: Request
  url: URL
  userPath: string
  localPath: string
  key: AccessKey
  isLegacyPath: boolean
}): Promise<Response> {
  const { url, userPath, localPath, key } = args
  const access = await accessStatusForLocalPath(localPath, key.value)

  if (access.hidden) {
    return textResponse("Not found", 404)
  }
  if (access.requiresPassword && !access.authorized) {
    return textResponse("Authentication required", 401)
  }

  const metadata = await readMetadata(localPath)
  if (metadata?.hidden === true) {
    return textResponse("Not found", 404)
  }

  if (config.hashEnabled && (config.hashRequired || url.searchParams.has("hash") || metadata?.hash_required)) {
    const stat = await fsp.stat(localPath)
    if (!hashFileAllowed(stat.size)) {
      return textResponse("Hashing disabled: file exceeds HASH_MAX_FILE_SIZE_MB", 413)
    }
    const expectedHash = await getHash(localPath)
    const providedHash = url.searchParams.get("hash")
    if (!providedHash) {
      return textResponse("Access denied: hash required", 403)
    }
    if (!expectedHash || expectedHash !== providedHash) {
      return textResponse("Access denied: invalid hash", 403)
    }
  }

  const stat = await fsp.stat(localPath)
  const mimeType = getMimeType(localPath)

  if (url.searchParams.has("info")) {
    if (!config.apiEnabled) {
      return textResponse("API disabled", 404)
    }
    const payload: Record<string, unknown> = {
      url: userPath,
      name: path.basename(localPath),
      mime: mimeType,
      size: stat.size,
      modified: Math.floor(stat.mtimeMs / 1000),
      downloads: counterStore.get(userPath),
    }
    if (config.hashEnabled && hashFileAllowed(stat.size)) {
      payload[`hash_${config.hashAlgo}`] = await getHash(localPath)
    }
    return jsonResponse(payload)
  }

  if (url.searchParams.has("preview")) {
    const extension = path.extname(localPath).replace(/^\./, "").toLowerCase()
    let kind = inferPreviewKind(mimeType, extension)
    const preview: Record<string, unknown> = {
      kind,
      mime: mimeType,
      truncated: false,
      text: null,
    }

    if (kind === "text" || kind === "json" || kind === "markdown") {
      const maxBytes = 128 * 1024
      const bytes = new Uint8Array(await Bun.file(localPath).slice(0, maxBytes + 1).arrayBuffer())
      const truncated = bytes.length > maxBytes
      const text = new TextDecoder().decode(truncated ? bytes.slice(0, maxBytes) : bytes)
      preview.truncated = truncated

      if (kind === "json") {
        try {
          preview.text = JSON.stringify(JSON.parse(text), null, 2)
        } catch {
          kind = "text"
          preview.kind = "text"
          preview.text = text
        }
      } else if (kind === "markdown") {
        try {
          preview.text = Bun.markdown.html(text)
        } catch {
          kind = "text"
          preview.kind = "text"
          preview.text = text
        }
      } else {
        preview.text = text
      }
    }

    return jsonResponse({
      url: userPath,
      name: path.basename(localPath),
      mime: mimeType,
      size: stat.size,
      size_human: `${(stat.size / 1024).toFixed(2)} KB`,
      modified: new Date(stat.mtimeMs).toISOString(),
      downloads: counterStore.get(userPath),
      preview,
    })
  }

  if (url.searchParams.has("raw")) {
    const headers = new Headers({
      "Content-Type": mimeType,
      "Cache-Control": "no-store",
    })
    withCorsHeaders(headers)
    return new Response(Bun.file(localPath), { headers })
  }

  counterStore.increment(userPath)
  const headers = new Headers({
    "Content-Type": mimeType,
    "Content-Disposition": `attachment; filename=\"${sanitizeHeaderValue(path.basename(localPath))}\"`,
    "Cache-Control": "no-store",
  })
  withCorsHeaders(headers)
  return new Response(Bun.file(localPath), { headers })
}

async function handleFileSystemRequest(req: Request, url: URL, userPathRaw: string, isLegacyPath: boolean): Promise<Response> {
  const userPath = normalizeUserPath(userPathRaw)
  const localPath = await resolveExistingLocalPath(userPath)
  const parsedBody = await resolveRequestBody(req)
  const key = extractAccessKey(req, url, parsedBody)

  if (!localPath) {
    if (shouldTreatAsFsRequest(url, req)) {
      return textResponse("Not found", 404)
    }
    return new Response("NOT_FILESYSTEM", { status: 460 })
  }

  const stat = await fsp.stat(localPath)
  if (stat.isDirectory()) {
    return handleDirectoryRequest({
      req,
      url,
      userPath,
      localPath,
      key,
      parsedBody,
      isLegacyPath,
    })
  }

  return handleFileRequest({
    req,
    url,
    userPath,
    localPath,
    key,
    isLegacyPath,
  })
}

async function proxyToNext(req: Request): Promise<Response> {
  const targetUrl = new URL(req.url)
  targetUrl.protocol = "http:"
  targetUrl.hostname = config.nextHost
  targetUrl.port = String(config.nextPort)

  const upstream = new Request(targetUrl.toString(), req)
  return fetch(upstream)
}

function isNextAssetPath(pathname: string): boolean {
  return pathname.startsWith("/_next") || pathname.startsWith("/favicon") || pathname.startsWith("/assets")
}

const server = Bun.serve({
  port: config.port,
  async fetch(req) {
    const url = new URL(req.url)

    if (req.method === "OPTIONS") {
      const headers = new Headers()
      withCorsHeaders(headers)
      return new Response(null, { status: 204, headers })
    }

    const globalAuthResponse = await requireGlobalAuth(req)
    if (globalAuthResponse) {
      return globalAuthResponse
    }

    if (url.pathname === "/api/status") {
      return jsonResponse({
        ok: true,
        title: config.title,
      })
    }

    if (url.pathname === config.uiBasePath) {
      return Response.redirect(`${config.uiBasePath}/`, 302)
    }

    if (url.pathname.startsWith(`${config.uiBasePath}/`) || isNextAssetPath(url.pathname)) {
      return proxyToNext(req)
    }

    if (url.pathname.startsWith("/api/fs")) {
      const suffix = url.pathname.replace(/^\/api\/fs/, "") || "/"
      return handleFileSystemRequest(req, url, suffix, false)
    }

    const legacyResponse = await handleFileSystemRequest(req, url, url.pathname, true)
    if (legacyResponse.status !== 460) {
      return legacyResponse
    }

    return proxyToNext(req)
  },
})

console.log(`dir-browser Bun server running at ${server.url}`)
console.log(`PUBLIC_FOLDER=${config.publicFolder}`)