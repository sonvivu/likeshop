import ElementPlus from 'element-plus'
import en from 'element-plus/dist/locale/en.mjs'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(ElementPlus, { locale: en })
})
