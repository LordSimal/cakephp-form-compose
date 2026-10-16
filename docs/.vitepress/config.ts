import { defineConfig } from 'vitepress';

const repository = 'https://github.com/josbeir/cakephp-form-compose';

export default defineConfig({
  title: 'FormCompose',
  description: 'A CakePHP FormHelper replacement with PHP element templates and composable fields.',
  lang: 'en-US',
  base: '/cakephp-form-compose/',
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
          { text: 'Create a theme', link: '/themes' },
        ],
      },
      {
        text: 'Reusable forms',
        items: [
          { text: 'Field definitions', link: '/definitions' },
          { text: 'Form blueprints', link: '/blueprints' },
          { text: 'Nested definitions and layouts', link: '/composition' },
        ],
      },
      {
        text: 'API reference',
        items: [
          { text: 'Helper and form session', link: '/form-reference' },
          { text: 'Field options', link: '/field-options' },
          { text: 'Bound fields', link: '/field-reference' },
          { text: 'Definitions and blueprints', link: '/definition-reference' },
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
