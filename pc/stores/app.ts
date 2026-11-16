import { defineStore } from 'pinia'
import Cookies from 'js-cookie'
import { createConsumeToken, clearConsumeToken } from '~/utils/consumeToken'

export const useAppStore = defineStore('app', {
  state: () => ({
    token: '',
    category: [],
    publicData: {},
    config: {}
  }),

  getters: {
    isLogin: (state) => !!state.token,
    headTitle: (state) => state.config.name,
    favicon: (state) => state.config.web_favicon,
    getSiteStatistics: (state) => state.config.site_statistic
  },

  actions: {
    setToken(token) {
      this.token = token
      if (token) {
        createConsumeToken(token).catch(() => {})
      }
    },
    logout() {
      this.token = ''
      Cookies.remove('token')
      clearConsumeToken()
    },
    setCategory(data) {
      this.category = data
    },
    setPublic(data) {
      this.publicData = data
    },
    setConfig(data) {
      this.config = data
    },
    async getCategory() {
      const { $get } = useNuxtApp()
      const { data, code } = await $get('goods_category/lists', {
        params: { client: 2 }
      })
      if (code == 1) {
        this.setCategory(data)
      }
    },
    async getPublicData() {
      const { $get } = useNuxtApp()
      const { data, code } = await $get('pc/commonData')
      if (code == 1) {
        this.setPublic(data)
      }
    },
    async getConfig() {
      const { $get } = useNuxtApp()
      const { data, code } = await $get('index/config')
      if (code == 1) {
        this.setConfig(data)
      }
    }
  }
})
