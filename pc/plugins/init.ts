import { useAppStore } from '~/stores/app'

export default defineNuxtPlugin(() => {
  const store = useAppStore()
  const token = useCookie('token').value
  if (token) {
    store.setToken(token)
  }
  store.getCategory()
  store.getPublicData()
  store.getConfig()
})
