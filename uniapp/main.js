import { createSSRApp } from 'vue'
import App from './App.vue'
import store from './store'
import i18n from './utils/i18n'
import { toast, copy, getImageUri } from './utils/tools'
import Cache from './utils/cache'
import minxinsApp from '@/mixins/app'
import uviewPlus from 'uview-plus'

export function createApp() {
    const app = createSSRApp(App)
    app.config.globalProperties.$toast = toast
    app.config.globalProperties.$copy = copy
    app.config.globalProperties.$Cache = Cache
    app.config.globalProperties.$getImageUri = getImageUri
    app.use(store)
    app.use(i18n)
    app.use(uviewPlus)
    app.mixin(minxinsApp)
    return {
        app
    }
}
