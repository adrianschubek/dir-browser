export type FileKind = "file" | "dir"

export type BrowserItem = {
  url: string
  name: string
  type: FileKind
  size: number
  modified: string
  downloads: number
  auth_required?: boolean
  auth_locked?: boolean
  metadata?: {
    description?: string
    labels?: string[]
    hash_required?: boolean
  }
}

export type ListDirectoryResponse = {
  path: string
  readme_html: string | null
  pagination_per_page: number
  items: BrowserItem[]
}

export type SearchDirectoryResponse = {
  total: number
  truncated: boolean
  base_folder: string
  results: Array<{
    url: string
    name: string
    is_dir: boolean
    auth_required: boolean
    auth_locked: boolean
  }>
}

export type FileInfoResponse = {
  url: string
  name: string
  mime: string
  size: number
  modified: number
  downloads: number
  [key: string]: unknown
}

export type FilePreviewResponse = {
  url: string
  name: string
  mime: string
  size: number
  size_human: string
  modified: string
  downloads: number
  preview: {
    kind: "text" | "json" | "markdown" | "image" | "pdf" | "video" | "audio" | "none"
    mime: string
    truncated: boolean
    text: string | null
  }
}
