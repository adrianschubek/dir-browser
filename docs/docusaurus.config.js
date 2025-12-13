// @ts-check
// Note: type annotations allow type checking and IDEs autocompletion

const lightCodeTheme = require('prism-react-renderer/themes/oceanicNext');
const darkCodeTheme = require('prism-react-renderer/themes/oceanicNext');

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'dir-browser',
  tagline: 'Dir Browser',
  // favicon: 'img/favicon.ico',

  // Set the production url of your site here
  url: 'https://dir.adriansoftware.de',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/',

  // GitHub pages deployment config.
  // If you aren't using GitHub pages, you don't need these.
  organizationName: 'adrianschubek', // Usually your GitHub org/user name.
  projectName: 'dir-browser', // Usually your repo name.

  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'warn',

  // Even if you don't use internalization, you can use this field to set useful
  // metadata like html lang. For example, if your site is Chinese, you may want
  // to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  markdown: {
    mermaid: true,
  },

  themes: ['@docusaurus/theme-mermaid'],

  plugins: [
    [
      '@docusaurus/plugin-ideal-image',
      {
        quality: 100,
        // max: 1030, // max resized image's size.
        // min: 640, // min resized image's size. if original is lower, use that size.
        steps: 2, // the max number of images generated between min and max (inclusive)
        disableInDev: false,
      },
    ],
    require.resolve("docusaurus-plugin-image-zoom"),
  ],

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          routeBasePath: '/',
          sidebarPath: require.resolve('./sidebars.js'),
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl:
            'https://github.com/adrianschubek/dir-browser/tree/main/docs/',

          sidebarCollapsible: false,
          showLastUpdateAuthor: false,
          showLastUpdateTime: true,
          lastVersion: 'current',
          versions: {
            "0.1.x": {
              label: 'v0',
              banner: "unmaintained",
              path: '/v0',
            },
            "1.x": {
              label: 'v1',
              banner: "unmaintained",
              path: '/v1',
            },
            "2.x": {
              label: 'v2',
              banner: "unmaintained",
              path: '/v2',
            },
            "3.x": {
              label: 'v3',
              banner: "unmaintained",
              path: '/v3',
            },
            "current": {
              label: 'v4 (latest✅)',
              banner: "none",
              path: '/v4',
            },
          },
        },
        blog: false,
        theme: {
          customCss: require.resolve('./src/css/custom.css'),
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      // Replace with your project's social card
      image: 'img/dir-browser.png',
      zoom: {
        selector: '.markdown :not(em) > img',
        config: {
          // options you can specify via https://github.com/francoischalifour/medium-zoom#usage
          background: {
            light: 'rgb(255, 255, 255)',
            dark: 'rgb(50, 50, 50)'
          }
        }
      },

      announcementBar: {
        id: 'abar',
        content:
          '🔥 dir-browser <a href="/v3/intro">v3.0</a> released with tons of new features and fresh UI 🔥',
        backgroundColor: '#fafbfc',
        textColor: '#091E42',
        isCloseable: true,
      },

      algolia: {
        // The application ID provided by Algolia
        appId: '18XYMP4MYT',
  
        // Public API key: it is safe to commit it
        apiKey: '99f292dc5fa4b7fa1e9afe6abd7601d2',
  
        indexName: 'dir-adriansoftware',
  
        // Optional: see doc section below
        contextualSearch: true,
  
        // Optional: Specify domains where the navigation should occur through window.location instead on history.push. Useful when our Algolia config crawls multiple documentation sites and we want to navigate with window.location.href to them.
        // externalUrlRegex: 'external\\.com|domain\\.com',
  
        // Optional: Replace parts of the item URLs from Algolia. Useful when using the same search index for multiple deployments using a different baseUrl. You can use regexp or string in the `from` param. For example: localhost:3000 vs myCompany.com/docs
        replaceSearchResultPathname: {
          from: '/docs/', // or as RegExp: /\/docs\//
          to: '/',
        },
  
        // Optional: Algolia search parameters
        searchParameters: {},
  
        // Optional: path for search page that enabled by default (`false` to disable it)
        searchPagePath: 'search',
  
        //... other Algolia params
      },
      navbar: {
        title: 'dir-browser',
        /* logo: {
          alt: 'My Site Logo',
          // src: 'img/logo.svg',
          src:'img/dir-browser.png'
        }, */
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'tutorialSidebar',
            position: 'left',
            label: 'Docs',
          }, /*
            { to: '/blog', label: 'Blog', position: 'left' }, */
          {
            type: 'docsVersionDropdown',
            position: 'right',
          },
          {
            href: 'https://dir-demo.adriansoftware.de',
            label: 'Demo',
            position: 'right',
          },
          {
            href: 'https://github.com/adrianschubek/dir-browser',
            className: 'header-github-link',
            position: 'right',
          },
        ],
      },
      colorMode: {
        defaultMode: 'light',
        disableSwitch: false,
        respectPrefersColorScheme: true,
      },
      footer: {
        style: 'dark',
        links: [
          /*  {
             title: 'Docs',
             items: [
               {
                 label: 'Tutorial',
                 to: '/docs/intro',
               },
             ],
           }, */
          /*  {
             title: 'Community',
             items: [
               {
                 label: 'Stack Overflow',
                 href: 'https://stackoverflow.com/questions/tagged/docusaurus',
               },
               {
                 label: 'Discord',
                 href: 'https://discordapp.com/invite/docusaurus',
               },
               {
                 label: 'Twitter',
                 href: 'https://twitter.com/docusaurus',
               },
             ],
           },
           {
             title: 'More',
             items: [
               {
                 label: 'Blog',
                 to: '/blog',
               },
               {
                 label: 'GitHub',
                 href: 'https://github.com/facebook/docusaurus',
               },
             ],
           }, */
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Adrian Schubek`,
      },
      prism: {
        theme: lightCodeTheme,
        darkTheme: darkCodeTheme,
        additionalLanguages: ['php','json'],
        magicComments: [
          {
            className: 'theme-code-block-highlighted-line',
            line: 'highlight-next-line',
            block: { start: 'highlight-start', end: 'highlight-end' },
          },
          {
            className: 'code-block-red-line',
            line: 'red-next-line',
          },
          {
            className: 'code-block-green-line',
            line: 'green-next-line',
          },
        ],
      },
    }),
};

module.exports = config;
