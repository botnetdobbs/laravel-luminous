import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Luminous',
  description:
    'PHP 8 Attribute-driven OpenAPI 3.2 documentation for Laravel. No YAML files to maintain.',
  base: '/laravel-luminous/',
  cleanUrls: false,
  lastUpdated: true,

  head: [
    ['link', { rel: 'icon', href: '/laravel-luminous/favicon.svg', type: 'image/svg+xml' }],
    ['meta', { name: 'theme-color', content: '#101726' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Luminous' }],
    [
      'meta',
      {
        property: 'og:description',
        content:
          'Generate OpenAPI 3.2 docs from PHP 8 Attributes on your Laravel controllers.',
      },
    ],
  ],

  themeConfig: {
    logo: { src: '/logo.svg', alt: 'Luminous' },
    siteTitle: 'Luminous',
    outline: { level: [2, 3] },
    search: {
      provider: 'local',
    },
    nav: [
      { text: 'Guide', link: '/introduction', activeMatch: '^/(introduction|configuration|controllers|form-requests|resources|shape-builder|security|deployment)' },
      { text: 'Attributes', link: '/attributes' },
      { text: 'FAQ', link: '/faq' },
      {
        text: 'Packagist',
        link: 'https://packagist.org/packages/botnetdobbs/laravel-luminous',
      },
    ],
    sidebar: [
      {
        text: 'Getting started',
        items: [
          { text: 'Introduction', link: '/introduction' },
          { text: 'Installation', link: '/installation' },
          { text: 'Quick look', link: '/quick-look' },
        ],
      },
      {
        text: 'Guide',
        items: [
          { text: 'Configuration', link: '/configuration' },
          { text: 'Controllers', link: '/controllers' },
          { text: 'Form requests', link: '/form-requests' },
          { text: 'API resources', link: '/resources' },
          { text: 'Shape builder', link: '/shape-builder' },
          { text: 'Security', link: '/security' },
          { text: 'CLI and deployment', link: '/deployment' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'Attribute reference', link: '/attributes' },
          { text: 'Common questions', link: '/faq' },
        ],
      },
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/botnet-dobbs/laravel-luminous' },
    ],
    editLink: {
      pattern: 'https://github.com/botnet-dobbs/laravel-luminous/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Lazarus Odhiambo',
    },
    docFooter: {
      prev: 'Previous',
      next: 'Next',
    },
  },
})
