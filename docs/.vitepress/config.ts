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
    nav: [
      { text: 'Why FormCompose?', link: '/why' },
      { text: 'How it works', link: '/rendering' },
      { text: 'Get started', link: '/getting-started' },
    ],
    sidebar: [
      {
        text: 'Understand FormCompose',
        items: [
          { text: 'Why FormCompose?', link: '/why' },
          { text: 'How rendering works', link: '/rendering' },
          { text: 'Getting started', link: '/getting-started' },
        ],
      },
      {
        text: 'Build your forms',
        items: [
          { text: 'Fields and form data', link: '/usage' },
          { text: 'Association fields', link: '/associations' },
          { text: 'Reusable definitions', link: '/definitions' },
          { text: 'Form blueprints', link: '/blueprints' },
          { text: 'Nested definitions and layouts', link: '/composition' },
          { text: 'Create a theme', link: '/themes' },
        ],
      },
      {
        text: 'Reference and extensions',
        items: [
          { text: 'Template reference', link: '/template-reference' },
          { text: 'Custom controls and services', link: '/extending' },
        ],
      },
      {
        text: 'Project',
        collapsed: true,
        items: [{ text: 'Contributing', link: '/contributing' }],
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
