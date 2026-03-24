import { FileBrowserPage } from "@/components/browser/file-browser-page"

type PageProps = {
  params: Promise<{
    path: string[]
  }>
}

export default async function BrowserPathPage({ params }: PageProps) {
  const resolved = await params
  const initialPath = `/${resolved.path.join("/")}`

  return <FileBrowserPage initialPath={initialPath} />
}
