import { useAppStore } from '~/stores/app'

const account = ['login', 'register', 'forget_pwd', 'error']

export default defineNuxtRouteMiddleware((to) => {
  const { fullPath } = to
  const isAccount = account.some((item) => fullPath.includes(item))
  const store = useAppStore()
  if (!isAccount && !store.isLogin) {
    useCookie('back_url').value = fullPath
  }
})
