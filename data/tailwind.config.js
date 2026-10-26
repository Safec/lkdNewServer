/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './assets/**/*.js',
        './assets/**/*.scss',
        './assets/**/*.html',
        './templates/**/*.html',
        './templates/**/*.html.twig',
    ],
    theme: {
        extend: {
            colors: {

                // Фирменные цвета
                'primary': 'rgb(var(--color-primary) / <alpha-value>)',
                'primary-hover': 'rgb(var(--color-primary-hover) / <alpha-value>)',
                'primary-pressed': 'rgb(var(--color-primary-pressed) / <alpha-value>)',
                'primary-light': 'rgb(var(--color-primary-light) / <alpha-value>)',
                'dandelion': 'rgb(var(--color-dandelion) / <alpha-value>)',
                'caper': 'rgb(var(--color-caper) / <alpha-value>)',
                'buttermilk': 'rgb(var(--color-buttermilk) / <alpha-value>)',
                'diamond': 'rgb(var(--color-diamond) / <alpha-value>)',

                // Дополнительные цвета
                'rose': 'rgb(var(--color-rose) / <alpha-value>)',
                'sunglow': 'rgb(var(--color-sunglow) / <alpha-value>)',
                'atlantis': 'rgb(var(--color-atlantis) / <alpha-value>)',

                // Оттенки серого
                'mine-shaft': 'rgb(var(--color-mine-shaft) / <alpha-value>)',
                'dim': 'rgb(var(--color-dim) / <alpha-value>)',
                'nobel': 'rgb(var(--color-nobel) / <alpha-value>)',
                'dark-silver': 'rgb(var(--color-dark-silver) / <alpha-value>)',
                'silver': 'rgb(var(--color-silver) / <alpha-value>)',
                'whisper': 'rgb(var(--color-whisper) / <alpha-value>)',
                'gallery': 'rgb(var(--color-gallery) / <alpha-value>)',
                'concrete': 'rgb(var(--color-concrete) / <alpha-value>)',
                'wild-sand': 'rgb(var(--color-wild-sand) / <alpha-value>)',

            },

            fontFamily: {
                'sans': ['Gilroy', 'sans-serif'],
                'icons': ['Icons'],
            },

            backgroundImage: {
                'icon-error': 'url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTguMDAwNDIgMEMzLjU4MTQ4IDAgNC4zNDA5ZS0wOCAzLjU4MTQ4IDQuMzQwOWUtMDggNy45OTk1OEMtMC4wMDAxMDk0MDYgOS4wNTAyNCAwLjIwNjc1MyAxMC4wOTA2IDAuNjA4NzczIDExLjA2MTNDMS4wMTA3OSAxMi4wMzIgMS42MDAwOSAxMi45MTQgMi4zNDMwMiAxMy42NTdDMy4wODU5NSAxNC4zOTk5IDMuOTY3OTYgMTQuOTg5MiA0LjkzODY2IDE1LjM5MTJDNS45MDkzNiAxNS43OTMyIDYuOTQ5NzYgMTYuMDAwMSA4LjAwMDQyIDE2QzEyLjQxNzcgMTYgMTYgMTIuNDE4NSAxNiA3Ljk5OTU4QzE2IDMuNTgxNDggMTIuNDE3NyAwIDguMDAwNDIgMFpNOC43NDcwNCAyLjg4ODE4QzkuNTI3IDIuODg4MTggOS43NTYxNiAzLjM0MDY2IDkuNzU2MTYgMy44NTgxM0M5Ljc1NjE2IDQuNTAzOTMgOS4yMzk1MiA1LjEwMTQgOC4zNTcwNyA1LjEwMTRDNy42MTg3NyA1LjEwMTQgNy4yNjcxMiA0LjczMDU5IDcuMjg4NzkgNC4xMTY0NUM3LjI4ODc5IDMuNTk4OTggNy43MjEyNiAyLjg4ODE4IDguNzQ3MDQgMi44ODgxOFpNNi43NDg4MiAxMi43OTFDNi4yMTU1MSAxMi43OTEgNS44MjYzNiAxMi40NjY5IDYuMTk4ODQgMTEuMDQ2MUw2LjgwOTY1IDguNTI1MzlDNi45MTU0NyA4LjEyMjA4IDYuOTMyOTcgNy45NjA0MiA2LjgwOTY1IDcuOTYwNDJDNi42NTA0OSA3Ljk2MDQyIDUuOTU4MDIgOC4yMzg3NCA1LjU0OTcxIDguNTEzNzJMNS4yODM4OSA4LjA3NzkxQzYuNTc5NjYgNi45OTU0NyA4LjA2OTU4IDYuMzYwNSA4LjcwNzA1IDYuMzYwNUM5LjI0MDM1IDYuMzYwNSA5LjMyODY4IDYuOTkwNDcgOS4wNjI4NiA3Ljk2MDQyTDguMzYyOSAxMC42MTAzQzguMjM4NzQgMTEuMDc4NiA4LjI5MjA3IDExLjI0MDIgOC40MTYyMyAxMS4yNDAyQzguNTc2MjIgMTEuMjQwMiA5LjA5OTUzIDExLjA0NjkgOS42MTQ1IDEwLjY0MTFMOS45MTYxNSAxMS4wNDYxQzguNjU1MzggMTIuMzA2IDcuMjgxMjkgMTIuNzkxIDYuNzQ4ODIgMTIuNzkxWiIgZmlsbD0iI0ZFMDA2NiIvPgo8L3N2Zz4K")',
            },

            borderRadius: {
                'half': '50%',
            },
        },

        fontSize: {
            'xs': ['12px', '16px'],
            'sm': ['14px', '20px'],
            'base': ['16px', '24px'],
            'lg': ['20px', '28px'],
            'xl': ['24px', '32px'],
            '2xl': ['28px', '36px'],
            '3xl': ['36px', '44px'],
            '4xl': ['48px', '56px'],
        },

        screens: {
            'sm': 'var(--screen-sm)',
            'md': 'var(--screen-md)',
            'lg': 'var(--screen-lg)',
            'xl': 'var(--screen-xl)',
            '2xl': 'var(--screen-2xl)',

            'mob': { max: '599px' },
            'tab': { max: '1199px' },
        }
    },
    plugins: [],
}

