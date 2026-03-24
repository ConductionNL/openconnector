// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Open Connector',
  tagline: 'Synchronize data between Nextcloud and external sources',
  url: 'https://conductionnl.github.io',
  baseUrl: '/openconnector/',
  organizationName: 'conductionnl',
  projectName: 'openconnector',
  favicon: 'img/favicon.ico',
  onBrokenLinks: 'warn',
  onBrokenMarkdownLinks: 'warn',

  // Even if you don't use internationalization, you can use this field to set
  // useful metadata like html lang. For example, if your site is Chinese, you
  // may want to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          path: '../docs',
          sidebarPath: require.resolve('./sidebars.js'),
          editUrl: 'https://github.com/conductionnl/openconnector/tree/main/docusaurus/',
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
      navbar: {
        title: 'Open Connector',
        logo: {
          alt: 'Open Connector Logo',
          src: 'img/logo.svg',
        },
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'tutorialSidebar',
            position: 'left',
            label: 'Documentation',
          },
          {
            href: 'https://github.com/conductionnl/openconnector',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Documentation',
            items: [
              {
                label: 'Getting Started',
                to: '/docs/getting-started',
              },
              {
                label: 'Tutorial',
                to: '/docs/tutorial',
              },
            ],
          },
          {
            title: 'Community',
            items: [
              {
                label: 'GitHub',
                href: 'https://github.com/conductionnl/openconnector',
              },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Conduction. Built with Docusaurus.`,
      },
      prism: {
        theme: {
          plain: {
            color: "#393A34",
            backgroundColor: "#f6f8fa"
          },
          styles: []
        },
        darkTheme: {
          plain: {
            color: "#F8F8F2",
            backgroundColor: "#282A36"
          },
          styles: []
        }
      },
    }),
};

module.exports = config;
