/** @type {import('next').NextConfig} */
const nextConfig = {
  async rewrites() {
    const apiPort = process.env.PORT || "8080"
    const apiHost = process.env.API_HOST || "localhost"
    return [
      {
        source: "/api/:path*",
        destination: `http://${apiHost}:${apiPort}/api/:path*`,
      },
    ]
  },
}

export default nextConfig
