import Vue from 'vue'
import App from './App'
import store from './store'
import VueI18n from 'vue-i18n'
import i18n from './utils/i18n'
import { toast, copy, getImageUri } from './utils/tools'
import Cache from './utils/cache'
import minxinsApp from '@/mixins/app'
import uView from '@/components/uview-ui'
Vue.prototype.$toast = toast
Vue.prototype.$copy = copy
Vue.prototype.$Cache = Cache
Vue.prototype.$getImageUri = getImageUri

Vue.config.productionTip = false

Vue.use(VueI18n)
Vue.use(uView)
App.mpType = 'app'
Vue.mixin(minxinsApp)
const app = new Vue({
    ...App,
    store,
    i18n
})
app.$mount()
