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
  // Group source pages by sidebar section while retaining published page URLs.
  rewrites: {
    'start-here/why.md': 'why.md',
    'start-here/getting-started.md': 'getting-started.md',
    'start-here/migration.md': 'migration.md',
    'forms/usage.md': 'usage.md',
    'forms/choices.md': 'choices.md',
    'forms/associations.md': 'associations.md',
    'forms/custom-fields.md': 'custom-fields.md',
    'forms/error-summaries.md': 'error-summaries.md',
    'forms/post-actions.md': 'post-actions.md',
    'markup/rendering.md': 'rendering.md',
    'markup/field-parts.md': 'field-parts.md',
    'markup/templates.md': 'templates.md',
    'markup/themes.md': 'themes.md',
    'markup/variants.md': 'variants.md',
    'markup/theme-debug.md': 'theme-debug.md',
    'reusable-forms/index.md': 'reusable-forms.md',
    'reusable-forms/blueprints.md': 'blueprints.md',
    'reusable-forms/composition.md': 'composition.md',
    'reusable-forms/repeated-embeds.md': 'repeated-embeds.md',
    'reference/form-reference.md': 'form-reference.md',
    'reference/field-options.md': 'field-options.md',
    'reference/field-reference.md': 'field-reference.md',
    'reference/blueprint-reference.md': 'blueprint-reference.md',
    'reference/template-reference.md': 'template-reference.md',
    'advanced/extending.md': 'extending.md',
    'advanced/blueprint-configuration.md': 'blueprint-configuration.md',
    'advanced/contributing.md': 'contributing.md',
  },
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
      { text: 'Reusable forms', link: '/reusable-forms' },
      { text: 'API reference', link: '/form-reference' },
    ],
    sidebar: [
      {
        text: 'Start here',
        items: [
          { text: 'Why FormCompose?', link: '/why' },
          { text: 'Your first form', link: '/getting-started' },
          { text: 'Migrate a core form', link: '/migration' },
        ],
      },
      {
        text: 'Build your forms',
        items: [
          { text: 'Fields and values', link: '/usage' },
          { text: 'Choice controls', link: '/choices' },
          { text: 'Association fields', link: '/associations' },
          { text: 'Custom field types', link: '/custom-fields' },
          { text: 'Error summaries', link: '/error-summaries' },
          { text: 'POST buttons and links', link: '/post-actions' },
        ],
      },
      {
        text: 'Markup and styling',
        items: [
          { text: 'How rendering works', link: '/rendering' },
          { text: 'Compose field parts', link: '/field-parts' },
          { text: 'Use your own templates', link: '/templates' },
          { text: 'Create a theme', link: '/themes' },
          { text: 'Use variants', link: '/variants' },
          { text: 'Debug template selection', link: '/theme-debug' },
        ],
      },
      {
        text: 'Reusable forms',
        items: [
          { text: 'Reusable forms', link: '/reusable-forms' },
          { text: 'Form blueprints', link: '/blueprints' },
          { text: 'Nested blueprints', link: '/composition' },
          { text: 'Repeated embeds', link: '/repeated-embeds' },
        ],
      },
      {
        text: 'API reference',
        items: [
          { text: 'Helper and form session', link: '/form-reference' },
          { text: 'Field configuration', link: '/field-options' },
          { text: 'Bound fields', link: '/field-reference' },
          { text: 'Blueprint reference', link: '/blueprint-reference' },
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
