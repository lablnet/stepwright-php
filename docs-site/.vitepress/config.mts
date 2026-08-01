import { defineConfig } from 'vitepress'
import { withMermaid } from "vitepress-plugin-mermaid"

export default withMermaid(defineConfig({
    title: "StepWright PHP",
    description: "A declarative, step-by-step approach to web automation and data extraction with Playwright in PHP.",
    srcDir: './src',
    outDir: './dist',
    cleanUrls: true,
    lastUpdated: true,

    head: [
        ['meta', { name: 'theme-color', content: '#4F5D95' }],
    ],

    themeConfig: {
        nav: [
            { text: 'Home', link: '/' },
            { text: 'Guide', link: '/guide/introduction' },
            { text: 'API Reference', link: '/api/reference' },
        ],

        sidebar: {
            '/guide/': [
                {
                    text: 'Introduction',
                    items: [
                        { text: 'What is StepWright PHP?', link: '/guide/introduction' },
                        { text: 'Installation', link: '/guide/installation' },
                        { text: 'Your First Scraper', link: '/guide/first-scraper' },
                        { text: 'Selectors & Data', link: '/guide/selectors-and-data' },
                        { text: 'Error Handling & Waits', link: '/guide/error-handling-and-waits' },
                        { text: 'Browser Options', link: '/guide/browser-options' },
                    ]
                },
                {
                    text: 'Advanced Features',
                    items: [
                        { text: 'Pagination & Scrolling', link: '/guide/advanced/pagination' },
                        { text: 'Parallelism & Templates', link: '/guide/advanced/parallelism' },
                        { text: 'Custom Drivers', link: '/guide/custom-driver' },
                    ]
                }
            ],
            '/api/': [
                {
                    text: 'API Reference',
                    items: [
                        { text: 'Overview & DTOs', link: '/api/reference' },
                        { text: 'Step Actions', link: '/api/actions' },
                    ]
                }
            ]
        },

        socialLinks: [
            { icon: 'github', link: 'https://github.com/lablnet/stepwright-php' }
        ],

        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2026-present Muhammad Umer Farooq'
        },

        search: {
            provider: 'local'
        }
    },

    vite: {
        optimizeDeps: {
            include: [
                '@braintree/sanitize-url',
                'dayjs',
                'dayjs/plugin/customParseFormat.js',
                'dayjs/plugin/advancedFormat.js',
                'dayjs/plugin/isoWeek.js',
                'debug',
                'cytoscape-cose-bilkent',
                'cytoscape'
            ]
        },
        ssr: {
            noExternal: [
                'dayjs',
                '@braintree/sanitize-url',
                'debug',
                'cytoscape-cose-bilkent',
                'cytoscape'
            ]
        }
    }
}))
