import { FileBrowserPage } from "@/components/browser/file-browser-page"

type PageProps = {
  params: Promise<{
    path?: string[]
  }>
}

export default async function UiBrowserPage({ params }: PageProps) {
  const resolved = await params
  const segments = resolved.path ?? []
  const initialPath = segments.length ? `/${segments.join("/")}` : "/"

  return <FileBrowserPage initialPath={initialPath} />
}
