"use client"

import { useCallback, useEffect, useMemo, useState } from "react"
import { useRouter } from "next/navigation"
import { useTheme } from "next-themes"
import {
  AudioLines,
  ChevronLeft,
  Code,
  Copy,
  Download,
  File,
  FileCode,
  FileImage,
  FileJson,
  FileSpreadsheet,
  FileText,
  Folder,
  Loader2,
  Moon,
  Search,
  Shield,
  Sun,
  Video,
  X
} from "lucide-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  getDirectoryReadme,
  getDownloadUrl,
  getFilePreview,
  getRawFileUrl,
  getUiPath,
  listDirectory,
  searchDirectory,
} from "@/lib/browser-api"
import {
  type BrowserItem,
  type FilePreviewResponse,
  type ListDirectoryResponse,
  type SearchDirectoryResponse,
} from "@/lib/browser-types"

const AUTH_STORAGE_KEY = "dir-browser-key"

type FileBrowserPageProps = {
  initialPath: string
}

function formatBytes(size: number): string {
  if (!Number.isFinite(size) || size <= 0) return "0 B"
  const units = ["B", "KB", "MB", "GB", "TB"]
  let value = size
  let index = 0
  while (value >= 1024 && index < units.length - 1) {
    value /= 1024
    index += 1
  }
  return `${value.toFixed(index === 0 ? 0 : 2)} ${units[index]}`
}

function formatDate(isoString: string): string {
  const date = new Date(isoString)
  if (Number.isNaN(date.valueOf())) return "-"
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  
  if (diffHours < 24 && diffHours > 0) {
    return `vor ${diffHours} Stunden`
  }
  
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function getErrorStatus(error: unknown): number | undefined {
  if (error && typeof error === "object" && "status" in error) {
    const status = Number((error as { status?: unknown }).status)
    if (Number.isFinite(status)) return status
  }
  return undefined
}

function getErrorMessage(error: unknown): string {
  if (error instanceof Error && error.message) return error.message
  return "Request failed"
}

function getFileIcon(item: BrowserItem) {
  if (item.type === "dir") return Folder
  const extension = item.name.split(".").pop()?.toLowerCase() ?? ""
  if (["png", "jpg", "jpeg", "gif", "webp", "bmp", "svg"].includes(extension)) return FileImage
  if (["mp4", "mov", "mkv", "webm", "avi"].includes(extension)) return Video
  if (["mp3", "wav", "ogg", "flac", "m4a"].includes(extension)) return AudioLines
  if (extension === "json" || extension === "dbmeta.json") return FileJson
  if (["csv", "xls", "xlsx"].includes(extension)) return FileSpreadsheet
  if (["md", "txt", "log", "yaml", "yml", "ini", "conf"].includes(extension)) return FileText
  if (["ts", "tsx", "js", "jsx", "php", "py", "go", "java", "rb", "rs"].includes(extension)) return FileCode
  if (["zip", "tar", "gz", "7z", "rar", "zst"].includes(extension)) return File
  return File
}

function decodePathSegment(segment: string): string {
  try {
    return decodeURIComponent(segment)
  } catch {
    return segment
  }
}

export function FileBrowserPage({ initialPath }: FileBrowserPageProps) {
  const router = useRouter()
  const { theme, setTheme } = useTheme()

  const [currentPath, setCurrentPath] = useState(initialPath)
  const [directoryData, setDirectoryData] = useState<ListDirectoryResponse | null>(null)
  const [readmeHtml, setReadmeHtml] = useState<string | null>(null)
  const [readmeLoading, setReadmeLoading] = useState(false)
  const [searchData, setSearchData] = useState<SearchDirectoryResponse | null>(null)
  const [searchText, setSearchText] = useState("")
  const [showSearch, setShowSearch] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [accessKey, setAccessKey] = useState("")
  const [authInput, setAuthInput] = useState("")
  const [authOpen, setAuthOpen] = useState(false)
  const [pendingRetry, setPendingRetry] = useState<null | (() => Promise<void>)>(null)

  const [previewOpen, setPreviewOpen] = useState(false)
  const [previewPath, setPreviewPath] = useState<string | null>(null)
  const [selectedItem, setSelectedItem] = useState<BrowserItem | null>(null)
  const [previewData, setPreviewData] = useState<FilePreviewResponse | null>(null)
  const [previewLoading, setPreviewLoading] = useState(false)

  useEffect(() => {
    setCurrentPath(initialPath)
    setReadmeHtml(null)
    setSearchData(null)
  }, [initialPath])

  useEffect(() => {
    const cached = window.localStorage.getItem(AUTH_STORAGE_KEY)
    if (cached) {
      setAccessKey(cached)
    }
  }, [])

  const requestUnlock = useCallback((retry: () => Promise<void>) => {
    setPendingRetry(() => retry)
    setAuthOpen(true)
  }, [])

  const loadDirectory = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const response = await listDirectory(currentPath, accessKey || undefined, false)
      setDirectoryData(response)
    } catch (requestError) {
      if (getErrorStatus(requestError) === 401) {
        requestUnlock(loadDirectory)
      } else {
        setError(getErrorMessage(requestError))
      }
    } finally {
      setLoading(false)
    }
  }, [accessKey, currentPath, requestUnlock])

  useEffect(() => {
    void loadDirectory()
  }, [loadDirectory])

  const loadReadme = useCallback(async () => {
    setReadmeLoading(true)
    try {
      const response = await getDirectoryReadme(currentPath, accessKey || undefined)
      setReadmeHtml(response.readme_html)
    } catch (requestError) {
      if (getErrorStatus(requestError) === 401) {
        requestUnlock(loadReadme)
      } else {
        setError(getErrorMessage(requestError))
      }
    } finally {
      setReadmeLoading(false)
    }
  }, [accessKey, currentPath, requestUnlock])

  useEffect(() => {
    void loadReadme()
  }, [loadReadme])

  const items = useMemo(() => {
    if (searchData) {
      return searchData.results.map<BrowserItem>((entry) => ({
        url: entry.url,
        name: entry.name,
        type: entry.is_dir ? "dir" : "file",
        size: 0,
        modified: "",
        downloads: 0,
        auth_required: entry.auth_required,
        auth_locked: entry.auth_locked,
      }))
    }
    return directoryData?.items ?? []
  }, [directoryData?.items, searchData])

  const openDirectory = (userPath: string) => {
    router.push(getUiPath(userPath))
  }

  const breadcrumbs = useMemo(() => {
    const segments = currentPath.split("/").filter(Boolean)
    const crumbs = [{ label: "/", path: "/" }]
    let pathAccumulator = ""

    for (const segment of segments) {
      pathAccumulator += `/${segment}`
      crumbs.push({
        label: decodePathSegment(segment),
        path: pathAccumulator,
      })
    }

    return crumbs
  }, [currentPath])

  const canGoBack = currentPath !== "/"

  const goToParentDirectory = () => {
    if (!canGoBack) return
    const segments = currentPath.split("/").filter(Boolean)
    const parentPath = segments.length <= 1 ? "/" : `/${segments.slice(0, -1).join("/")}`
    openDirectory(parentPath)
  }

  const previewRawUrl = previewPath ? getRawFileUrl(previewPath) : null

  const runSearch = async () => {
    if (!searchText.trim()) {
      setSearchData(null)
      return
    }
    setLoading(true)
    setError(null)

    try {
      const response = await searchDirectory(currentPath, searchText.trim(), "s", accessKey || undefined)
      setSearchData(response)
    } catch (requestError) {
      if (getErrorStatus(requestError) === 401) {
        requestUnlock(runSearch)
      } else {
        setError(getErrorMessage(requestError))
      }
    } finally {
      setLoading(false)
    }
  }

  const loadPreview = async (item: BrowserItem) => {
    setSelectedItem(item)
    setPreviewOpen(true)
    setPreviewPath(item.url)
    setPreviewData(null)
    setPreviewLoading(true)

    try {
      const response = await getFilePreview(item.url, accessKey || undefined)
      setPreviewData(response)
    } catch (requestError) {
      if (getErrorStatus(requestError) === 401) {
        requestUnlock(async () => {
          await loadPreview(item)
        })
        setPreviewOpen(false)
      } else {
        setError(getErrorMessage(requestError))
      }
    } finally {
      setPreviewLoading(false)
    }
  }

  const submitAccessKey = async () => {
    const candidate = authInput.trim()
    if (!candidate) return

    window.localStorage.setItem(AUTH_STORAGE_KEY, candidate)
    setAccessKey(candidate)
    setAuthOpen(false)

    const retry = pendingRetry
    setPendingRetry(null)
    if (retry) await retry()
  }

  return (
    <div className="mx-auto flex min-h-screen w-full max-w-[1000px] flex-col pb-16 pt-8">
      {/* Header */}
      <header className="mb-6 flex items-center justify-between px-4 sm:px-6">
        <div className="flex min-w-0 items-center gap-2">
          <Button
            variant="ghost"
            size="icon"
            className="size-9 shrink-0 rounded-full text-muted-foreground hover:text-foreground"
            disabled={!canGoBack}
            onClick={goToParentDirectory}
          >
            <ChevronLeft className="size-4" />
          </Button>

          <nav className="flex min-w-0 items-center text-sm font-medium text-muted-foreground">
            {breadcrumbs.map((crumb, index) => (
              <div key={crumb.path} className="flex min-w-0 items-center">
                {index > 0 && <span className="mx-1 text-muted-foreground/50">/</span>}
                <button
                  type="button"
                  className="max-w-[180px] truncate rounded-sm px-1 py-0.5 text-left text-foreground/80 transition-colors hover:text-foreground disabled:cursor-default disabled:text-foreground"
                  onClick={() => openDirectory(crumb.path)}
                  disabled={crumb.path === currentPath}
                >
                  {crumb.label}
                </button>
              </div>
            ))}
          </nav>
        </div>
        
        <div className="flex items-center gap-4">
          {showSearch ? (
            <div className="flex items-center gap-2">
              <Input 
                autoFocus
                placeholder="Search..."
                className="h-9 w-48 bg-background"
                value={searchText}
                onChange={(e) => setSearchText(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === "Enter") void runSearch()
                  if (e.key === "Escape") {
                    setShowSearch(false)
                    setSearchText("")
                    setSearchData(null)
                  }
                }}
              />
              <Button variant="ghost" size="icon" className="size-9 rounded-full" onClick={() => {
                setShowSearch(false)
                setSearchText("")
                setSearchData(null)
              }}>
                <X className="size-4" />
              </Button>
            </div>
          ) : (
            <Button variant="ghost" size="icon" className="size-9 rounded-full text-muted-foreground hover:text-foreground" onClick={() => setShowSearch(true)}>
              <Search className="size-4" />
            </Button>
          )}
          
          <Button 
            variant="ghost" 
            size="icon" 
            className="size-9 rounded-full text-muted-foreground hover:text-foreground" 
            onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
          >
            {theme === "dark" ? <Moon className="size-4" /> : <Sun className="size-4" />}
          </Button>
        </div>
      </header>

      {/* Main List */}
      <main className="flex-1 px-4 sm:px-6">
        {error && (
          <div className="mb-4 rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive border border-destructive/20">
            {error}
          </div>
        )}
        
        <div className="overflow-hidden rounded-xl border border-border/40 bg-card/40 backdrop-blur-md shadow-sm">
          <Table>
            <TableHeader>
              <TableRow className="border-border/40 hover:bg-transparent">
                <TableHead className="h-11 font-medium text-muted-foreground">Name</TableHead>
                <TableHead className="h-11 w-32 whitespace-nowrap text-right font-medium text-muted-foreground">Downloads</TableHead>
                <TableHead className="h-11 w-32 whitespace-nowrap text-right font-medium text-muted-foreground">Size</TableHead>
                <TableHead className="h-11 w-40 whitespace-nowrap text-right font-medium text-muted-foreground">Modified</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {loading && items.length === 0 && (
                <TableRow className="hover:bg-transparent">
                  <TableCell colSpan={4} className="h-32 text-center">
                    <Loader2 className="mx-auto size-5 animate-spin text-muted-foreground/50" />
                  </TableCell>
                </TableRow>
              )}
              {!loading && items.length === 0 && (
                <TableRow className="hover:bg-transparent">
                  <TableCell colSpan={4} className="h-32 text-center text-sm text-muted-foreground">
                    No files found.
                  </TableCell>
                </TableRow>
              )}
              {items.map((item) => {
                const Icon = getFileIcon(item)
                const isFile = item.type === "file"
                return (
                  <TableRow 
                    key={item.url}
                    className="cursor-pointer border-border/40 transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted"
                    onClick={() => isFile ? loadPreview(item) : openDirectory(item.url)}
                  >
                    <TableCell className="py-3">
                      <div className="flex items-center gap-3">
                        <Icon className={`size-4 shrink-0 ${isFile ? "text-muted-foreground/70" : "text-blue-500/80"}`} />
                        <span className="truncate font-medium text-foreground/90">{item.name}</span>
                        {item.auth_required && (
                          <Shield className="size-3 text-muted-foreground/50" />
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="py-3 text-right text-sm text-muted-foreground">
                      {item.downloads} <Download className="inline-block size-3 ml-1 opacity-50" />
                    </TableCell>
                    <TableCell className="py-3 text-right text-sm font-medium text-muted-foreground">
                      {isFile ? formatBytes(item.size) : "-"}
                    </TableCell>
                    <TableCell className="py-3 text-right text-sm font-medium text-muted-foreground">
                      {item.modified ? formatDate(item.modified) : "-"}
                    </TableCell>
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
        </div>

        {/* README Section */}
        {!searchData && (readmeLoading || readmeHtml) && (
          <div className="mt-8 rounded-xl border border-border/40 bg-card/40 backdrop-blur-md px-6 py-6 shadow-sm">
            {readmeLoading ? (
              <div className="flex items-center justify-center py-6 text-sm text-muted-foreground">
                <Loader2 className="mr-2 size-4 animate-spin" />
                Loading README...
              </div>
            ) : (
              <div
                className="text-sm leading-6 [&_h1]:text-2xl [&_h1]:font-semibold [&_h1]:mb-6 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:mt-8 [&_h2]:mb-4 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:mt-6 [&_h3]:mb-3 [&_p]:mb-4 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-4 [&_li]:mb-1 [&_a]:text-blue-500 [&_a:hover]:underline text-foreground/80 marker:text-foreground/80"
                dangerouslySetInnerHTML={{ __html: readmeHtml ?? "" }}
              />
            )}
          </div>
        )}
      </main>

      {/* Preview Dialog */}
      <Dialog open={previewOpen} onOpenChange={setPreviewOpen}>
        <DialogContent className="max-w-[500px] border-border/40 bg-background/95 p-0 backdrop-blur-xl gap-0 shadow-2xl overflow-hidden sm:rounded-2xl">
          <div className="flex items-center justify-between border-b border-border/40 px-6 py-4">
            <h2 className="text-base font-medium text-foreground/90 tracking-tight truncate pr-4">
              {selectedItem?.name ?? previewPath ?? "File"}
            </h2>
          </div>
          
          <div className="p-6">
            <div className="mb-6 rounded-lg border border-border/40 bg-muted/20 px-4 py-4 text-center text-sm text-muted-foreground">
              {previewLoading && <Loader2 className="mx-auto size-4 animate-spin" />}

              {!previewLoading && previewData?.preview.kind === "text" && (
                <pre className="max-h-[320px] overflow-auto whitespace-pre-wrap break-words text-left text-xs text-foreground/90">
                  {previewData.preview.text ?? ""}
                </pre>
              )}

              {!previewLoading && previewData?.preview.kind === "json" && (
                <pre className="max-h-[320px] overflow-auto whitespace-pre-wrap break-words text-left text-xs text-foreground/90">
                  {previewData.preview.text ?? ""}
                </pre>
              )}

              {!previewLoading && previewData?.preview.kind === "markdown" && (
                <div
                  className="max-h-[320px] overflow-auto text-left text-sm leading-6 text-foreground/90 [&_h1]:mb-4 [&_h1]:text-xl [&_h1]:font-semibold [&_h2]:mb-3 [&_h2]:mt-6 [&_h2]:text-lg [&_h2]:font-semibold [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_a]:text-blue-500 [&_a:hover]:underline"
                  dangerouslySetInnerHTML={{ __html: previewData.preview.text ?? "" }}
                />
              )}

              {!previewLoading && previewData?.preview.kind === "image" && previewRawUrl && (
                <img src={previewRawUrl} alt={selectedItem?.name ?? "Preview image"} className="mx-auto max-h-[320px] w-auto rounded-md" />
              )}

              {!previewLoading && previewData?.preview.kind === "pdf" && previewRawUrl && (
                <iframe src={previewRawUrl} title={selectedItem?.name ?? "PDF preview"} className="h-[360px] w-full rounded-md" />
              )}

              {!previewLoading && previewData?.preview.kind === "video" && previewRawUrl && (
                <video src={previewRawUrl} controls className="mx-auto max-h-[320px] w-full rounded-md" />
              )}

              {!previewLoading && previewData?.preview.kind === "audio" && previewRawUrl && (
                <audio src={previewRawUrl} controls className="w-full" />
              )}

              {!previewLoading && (!previewData || previewData.preview.kind === "none") && (
                <span>No preview available for this file.</span>
              )}
            </div>

            <div className="mb-2 flex items-center gap-2 text-sm font-semibold tracking-tight text-foreground/90">
              Metadata
              {previewData?.preview.truncated && <Badge variant="secondary">Truncated</Badge>}
            </div>
            
            <div className="rounded-lg border border-border/40 bg-card/50 overflow-hidden divide-y divide-border/40">
              <div className="flex items-center justify-between px-4 py-2.5 text-sm">
                <span className="text-muted-foreground font-medium">Size</span>
                <span className="text-foreground/90 font-medium">
                  {previewData ? formatBytes(previewData.size) : selectedItem ? formatBytes(selectedItem.size) : "-"}
                </span>
              </div>
              <div className="flex items-center justify-between px-4 py-2.5 text-sm">
                <span className="text-muted-foreground font-medium">Modified</span>
                <span className="text-foreground/90 font-medium">
                  {previewData?.modified
                    ? formatDate(previewData.modified)
                    : selectedItem?.modified
                      ? formatDate(selectedItem.modified)
                      : "-"}
                </span>
              </div>
              <div className="flex items-center justify-between px-4 py-2.5 text-sm">
                <span className="text-muted-foreground font-medium">Downloads</span>
                <span className="text-foreground/90 font-medium">{previewData?.downloads ?? selectedItem?.downloads ?? 0}</span>
              </div>
              <div className="flex items-center justify-between px-4 py-2.5 text-sm">
                <span className="text-muted-foreground font-medium">MIME</span>
                <span className="text-foreground/90 font-medium">{previewData?.mime ?? "application/octet-stream"}</span>
              </div>
              <div className="flex items-center justify-between px-4 py-2.5 text-sm bg-muted/10">
                <span className="text-muted-foreground font-medium">Hash (sha256)</span>
                <button className="text-blue-500 hover:text-blue-400 font-medium transition-colors">
                  Click to calculate hash
                </button>
              </div>
            </div>
          </div>

          <div className="flex items-center justify-end gap-2 border-t border-border/40 px-6 py-4 bg-muted/10">
            <Button variant="outline" size="sm" className="h-9 gap-2 font-medium bg-background" onClick={() => navigator.clipboard.writeText(selectedItem?.name ?? "")}>
              <Copy className="size-3.5" />
              Copy Text
            </Button>
            <Button variant="outline" size="sm" className="h-9 gap-2 font-medium bg-background">
              <Code className="size-3.5" />
              API
            </Button>
            <Button size="sm" className="h-9 gap-2 font-medium shadow-sm bg-blue-600 text-white hover:bg-blue-700" asChild>
              {previewPath ? (
                <a href={getDownloadUrl(previewPath)}>
                  <Download className="size-3.5" />
                  Download
                </a>
              ) : (
                <button disabled>
                  <Download className="size-3.5" />
                  Download
                </button>
              )}
            </Button>
          </div>
        </DialogContent>
      </Dialog>

      {/* Auth Dialog */}
      <Dialog open={authOpen} onOpenChange={setAuthOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Authentication Required</DialogTitle>
            <p className="text-sm text-muted-foreground">Enter the folder key to continue.</p>
          </DialogHeader>
          <Input
            type="password"
            value={authInput}
            onChange={(event) => setAuthInput(event.target.value)}
            placeholder="Access key"
            className="mt-2"
            onKeyDown={(event) => {
              if (event.key === "Enter") void submitAccessKey()
            }}
          />
          <div className="mt-4 flex justify-end gap-2">
            <Button variant="outline" onClick={() => setAuthOpen(false)}>Cancel</Button>
            <Button onClick={() => void submitAccessKey()}>Unlock</Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  )
}
