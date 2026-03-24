import {
  type FileInfoResponse,
  type FilePreviewResponse,
  type ListDirectoryResponse,
  type SearchDirectoryResponse,
} from "@/lib/browser-types"

function encodeUserPath(pathname: string): string {
  const normalized = pathname.startsWith("/") ? pathname : `/${pathname}`
  const segments = normalized.split("/").filter(Boolean).map(encodeURIComponent)
  return `/${segments.join("/")}`
}

function getApiPath(pathname: string): string {
  const encoded = encodeUserPath(pathname)
  return `/api/fs${encoded === "/" ? "/" : encoded}`
}

function withAuthHeaders(accessKey?: string): HeadersInit {
  if (!accessKey) return {}
  return { "X-Key": accessKey }
}

async function handleResponse<T>(response: Response): Promise<T> {
  if (!response.ok) {
    const message = await response.text()
    throw Object.assign(new Error(message || `Request failed: ${response.status}`), {
      status: response.status,
    })
  }
  return (await response.json()) as T
}

export async function listDirectory(pathname: string, accessKey?: string): Promise<ListDirectoryResponse> {
  const response = await fetch(`${getApiPath(pathname)}?ls`, {
    headers: withAuthHeaders(accessKey),
    cache: "no-store",
  })
  return handleResponse<ListDirectoryResponse>(response)
}

export async function searchDirectory(
  pathname: string,
  query: string,
  engine: "s" | "g" | "r",
  accessKey?: string
): Promise<SearchDirectoryResponse> {
  const response = await fetch(`${getApiPath(pathname)}?q=${encodeURIComponent(query)}&e=${engine}`, {
    headers: withAuthHeaders(accessKey),
    cache: "no-store",
  })
  return handleResponse<SearchDirectoryResponse>(response)
}

export async function getFileInfo(pathname: string, accessKey?: string): Promise<FileInfoResponse> {
  const response = await fetch(`${getApiPath(pathname)}?info`, {
    headers: withAuthHeaders(accessKey),
    cache: "no-store",
  })
  return handleResponse<FileInfoResponse>(response)
}

export async function getFilePreview(pathname: string, accessKey?: string): Promise<FilePreviewResponse> {
  const response = await fetch(`${getApiPath(pathname)}?preview`, {
    headers: withAuthHeaders(accessKey),
    cache: "no-store",
  })
  return handleResponse<FilePreviewResponse>(response)
}

export function getRawFileUrl(pathname: string): string {
  return `${getApiPath(pathname)}?raw=1`
}

export function getDownloadUrl(pathname: string): string {
  return getApiPath(pathname)
}

export function getUiPath(pathname: string): string {
  const normalized = pathname.startsWith("/") ? pathname : `/${pathname}`
  if (normalized === "/") return "/ui"
  const encoded = normalized
    .split("/")
    .filter(Boolean)
    .map(encodeURIComponent)
    .join("/")
  return `/ui/${encoded}`
}

export async function batchDownload(pathname: string, selectedPaths: string[], accessKey?: string): Promise<Blob> {
  const formData = new FormData()
  for (const item of selectedPaths) {
    formData.append("download_batch[]", item)
  }

  const response = await fetch(getApiPath(pathname), {
    method: "POST",
    headers: withAuthHeaders(accessKey),
    body: formData,
  })

  if (!response.ok) {
    const message = await response.text()
    throw Object.assign(new Error(message || `Request failed: ${response.status}`), {
      status: response.status,
    })
  }

  return response.blob()
}
