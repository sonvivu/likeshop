import { ElMessage } from 'element-plus'
import JSEncrypt from 'jsencrypt'
import config from '~/config/app'
import { createConsumeToken, refreshConsumeToken } from '~/utils/consumeToken'
import { useAppStore } from '~/stores/app'

// Port tu plugins/axios.js (Nuxt 2 + @nuxtjs/axios) sang $fetch/ofetch.
// Giu nguyen: baseURL /api/, header token + Accept-Language, ma hoa RSA
// cac truong password (qua account/passwordKey), retry consume-token,
// Message loi (code 0 + show), logout + ve login (code -1),
// redirect /error khi loi HTTP/mang.
const passwordFields = [
  'password', 'password2', 'password_confirm', 'passwordConfirm',
  're_password', 'repassword', 'confirm_password', 'confirmPassword',
  'old_password', 'oldPassword', 'curr_password', 'currPassword',
  'new_password', 'newPassword', 'pay_password', 'payPassword',
  'origin_pay_password', 'originPayPassword', 'new_pay_password', 'newPayPassword'
]

const baseURL = `${config.baseUrl}/api/`

export default defineNuxtPlugin((nuxtApp) => {
  const store = useAppStore()

  // Locale + t lay lazy tu $i18n (legacy mode: locale la string;
  // composition mode: locale la ref) — tranh phu thuoc kieu cu the.
  const getLocale = () => {
    const i18n = nuxtApp.$i18n
    const loc = i18n && i18n.locale
    const val = typeof loc === 'string' ? loc : loc && loc.value
    return val || 'vi'
  }
  const t = (key) => {
    const i18n = nuxtApp.$i18n
    try {
      return (i18n && i18n.t && i18n.t(key)) || key
    } catch {
      return key
    }
  }

  // Consume token cua request vua gui (de refresh khi gap
  // consume_token_invalid) — tuong duong headers['X-Consume-Token']
  // tren requestConfig cua axios cu.
  let lastConsumeToken = ''

  const rawApi = $fetch.create({
    baseURL,
    async onRequest({ options }) {
      const headers = new Headers(options.headers || {})
      if (!headers.get('token')) {
        headers.set('token', store.token || '')
      }
      headers.set('Accept-Language', getLocale())

      const body = options.body
      if (
        !options._consumeTokenRetry &&
        body && typeof body === 'object' && !Array.isArray(body) &&
        passwordFields.some((field) => typeof body[field] === 'string' && body[field])
      ) {
        // Lay khoa RSA bang $fetch tran (khong qua hook, tranh de quy).
        const keyRes = await $fetch('account/passwordKey', { baseURL })
        if (!keyRes || keyRes.code !== 1 || !keyRes.data || !keyRes.data.key) {
          throw new Error((keyRes && keyRes.msg) || t('获取密码加密密钥失败'))
        }
        const encryptor = new JSEncrypt()
        encryptor.setPublicKey(keyRes.data.key)
        const data = { ...body }
        passwordFields.forEach((field) => {
          if (typeof data[field] === 'string' && data[field]) {
            const encrypted = encryptor.encrypt(data[field])
            if (!encrypted) {
              throw new Error(t('密码加密失败'))
            }
            data[field] = `RSA:${encrypted}`
          }
        })
        data.password_key_id = keyRes.data.key_id || keyRes.data.keyId
        options.body = data
      }

      const token = headers.get('token')
      if (token) {
        lastConsumeToken = await createConsumeToken(token)
        if (lastConsumeToken) {
          headers.set('X-Consume-Token', lastConsumeToken)
        }
      }
      options.headers = headers
    },
    onResponseError() {
      navigateTo('/error')
    }
  })

  // Wrapper xu ly envelope code/msg/data giong $axios.onResponse cu.
  async function request(method, url, opts = {}) {
    const res = await rawApi(url, { method, ...opts })
    if (res && res.code == 0 && res.data && res.data.error === 'consume_token_invalid') {
      if (!opts._consumeTokenRetry) {
        await refreshConsumeToken(store.token, lastConsumeToken)
        return request(method, url, { ...opts, _consumeTokenRetry: true })
      }
      return res
    }
    if (res && res.code == 0 && res.show && res.msg) {
      ElMessage({ message: res.msg, type: 'error' })
    } else if (res && res.code == -1) {
      store.logout()
      navigateTo('/account/login')
    }
    return res
  }

  const get = (url, opts = {}) => request('GET', url, opts)
  const post = (url, data, opts = {}) => request('POST', url, { ...opts, body: data })

  nuxtApp.provide('get', get)
  nuxtApp.provide('post', post)
  nuxtApp.provide('api', rawApi)
})
