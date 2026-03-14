/** @type {import('next').NextConfig} */
const nextConfig = {
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: 'http://136.243.19.223/api/:path*',
      },
    ];
  },
};
module.exports = nextConfig;
