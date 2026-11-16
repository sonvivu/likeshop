// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: false,

  app: {
    baseURL: '/pc/',
    head: {
      title: '',
      htmlAttrs: {
        lang: 'vi'
      },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: '' }
      ]
    }
  },

  css: [
    'swiper/css',
    'swiper/css/navigation',
    'swiper/css/pagination',
    '~/assets/css/element-variables.scss',
    '~/assets/css/global.scss',
    '~/assets/fonts/iconfont.css'
  ],

  plugins: [
    '~/plugins/element-plus',
    '~/plugins/api',
    '~/plugins/init'
  ],

  modules: [
    '@pinia/nuxt',
    '@nuxtjs/i18n'
  ],

  i18n: {
    locales: [
      { code: 'vi', file: 'vi.json' },
      { code: 'zh', file: 'zh.json' }
    ],
    defaultLocale: 'vi',
    lazy: true,
    langDir: 'locales/',
    strategy: 'no_prefix',
    vueI18n: './i18n.config.ts',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
      fallbackLocale: 'vi'
    }
  },

  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          // element-variables.scss chua @forward phai dung dau file:
          // khong prepend @import vao no (sass se loi
          // "@forward rules must be written before any other rules").
          additionalData: (source: string, filename: string) =>
            filename.endsWith('element-variables.scss')
              ? source
              : `@import "~/assets/css/variable.scss";\n${source}`
        }
      }
    }
  },

  compatibilityDate: '2025-11-16'
})
