import { defineConfig } from 'vitepress';

const repository = 'https://github.com/josbeir/cakephp-form-compose';
const base = '/cakephp-form-compose/';
const hostname = `https://josbeir.github.io${base}`;
const socialImage = new URL('branding/formcompose-mark.png', hostname).href;

export default defineConfig({
  title: 'FormCompose',
  titleTemplate: ':title | FormCompose',
  description: 'A CakePHP FormHelper replacement with PHP element templates and composable fields.',
  lang: 'en-US',
  base,
  srcExclude: ['public/**'],
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: `${base}branding/formcompose-mark.svg` }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:site_name', content: 'FormCompose' }],
    ['meta', { property: 'og:locale', content: 'en_US' }],
    ['meta', { property: 'og:image', content: socialImage }],
    ['meta', { property: 'og:image:type', content: 'image/png' }],
    ['meta', { property: 'og:image:width', content: '512' }],
    ['meta', { property: 'og:image:height', content: '512' }],
    ['meta', { property: 'og:image:alt', content: 'FormCompose logo' }],
    ['meta', { name: 'twitter:card', content: 'summary' }],
    ['meta', { name: 'twitter:image', content: socialImage }],
    ['meta', { name: 'twitter:image:alt', content: 'FormCompose logo' }],
  ],
  sitemap: { hostname },
  transformPageData(pageData) {
    const path = pageData.relativePath
      .replace(/(^|\/)index\.md$/, '$1')
      .replace(/\.md$/, '.html');
    const url = new URL(path, hostname).href;
    const title = pageData.titleTemplate === false
      ? pageData.title
      : `${pageData.title} | FormCompose`;

    pageData.frontmatter.head ??= [];
    pageData.frontmatter.head.push(
      ['link', { rel: 'canonical', href: url }],
      ['meta', { property: 'og:title', content: title }],
      ['meta', { property: 'og:description', content: pageData.description }],
      ['meta', { property: 'og:url', content: url }],
      ['meta', { name: 'twitter:title', content: title }],
      ['meta', { name: 'twitter:description', content: pageData.description }],
    );
  },
  transformHead({ pageData }) {
    if (pageData.isNotFound) {
      return [['meta', { name: 'robots', content: 'noindex' }]];
    }
  },
  lastUpdated: true,
  markdown: {
    lineNumbers: true,
  },
  themeConfig: {
    logo: {
      src: '/branding/formcompose-mark.svg',
      alt: '',
      width: 24,
      height: 24,
    },
    nav: [
      { text: 'Guide', link: '/getting-started' },
      { text: 'Reusable forms', link: '/definitions' },
      { text: 'API reference', link: '/form-reference' },
    ],
    sidebar: [
      {
        text: 'Start here',
        items: [
          { text: 'Why FormCompose?', link: '/why' },
          { text: 'Your first form', link: '/getting-started' },
          { text: 'Migrate a core form', link: '/migration' },
          { text: 'How rendering works', link: '/rendering' },
        ],
      },
      {
        text: 'Build your forms',
        items: [
          { text: 'Fields and values', link: '/usage' },
          { text: 'Choice controls', link: '/choices' },
          { text: 'Association fields', link: '/associations' },
          { text: 'Compose field parts', link: '/field-parts' },
          { text: 'Custom field types', link: '/custom-fields' },
          { text: 'Error summaries', link: '/error-summaries' },
          { text: 'POST buttons and links', link: '/post-actions' },
          { text: 'Create a theme', link: '/themes' },
        ],
      },
      {
        text: 'Reusable forms',
        items: [
          { text: 'Reusable forms', link: '/definitions' },
          { text: 'Form blueprints', link: '/blueprints' },
          { text: 'Nested definitions', link: '/composition' },
          { text: 'Repeated embeds', link: '/repeated-embeds' },
        ],
      },
      {
        text: 'API reference',
        items: [
          { text: 'Helper and form session', link: '/form-reference' },
          { text: 'Field options', link: '/field-options' },
          { text: 'Bound fields', link: '/field-reference' },
          { text: 'Blueprint reference', link: '/definition-reference' },
          { text: 'Template reference', link: '/template-reference' },
        ],
      },
      {
        text: 'Advanced and project',
        collapsed: true,
        items: [
          { text: 'Custom controls and services', link: '/extending' },
          { text: 'Blueprint services and discovery', link: '/blueprint-configuration' },
          { text: 'Contributing', link: '/contributing' },
        ],
      },
    ],
    search: { provider: 'local' },
    outline: { level: [2, 3] },
    editLink: {
      pattern: `${repository}/edit/main/docs/:path`,
      text: 'Edit this page on GitHub',
    },
    socialLinks: [{ icon: 'github', link: repository }],
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'FormCompose contributors',
    },
  },
});
