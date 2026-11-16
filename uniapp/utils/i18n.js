import { createI18n } from 'vue-i18n'
import Cache from './cache'
import { LOCALE } from '../config/cachekey'
import zh from '../locales/zh.json'
import vi from '../locales/vi.json'

// vi.json co mot so key chua dich (gia tri rong). Loai bo de $t fallback
// ve msgid (chu Trung) thay vi tra ve chuoi rong lam trang giao dien.
function stripEmpty(messages) {
    const out = {}
    for (const key in messages) {
        if (messages[key]) out[key] = messages[key]
    }
    return out
}

const DEFAULT_LOCALE = 'vi'

function getSavedLocale() {
    const saved = Cache.get(LOCALE)
    return saved === 'vi' || saved === 'zh' ? saved : DEFAULT_LOCALE
}

const i18n = createI18n({
    legacy: true,
    locale: getSavedLocale(),
    fallbackLocale: 'zh',
    missingWarn: false,
    fallbackWarn: false,
    messages: {
        vi: stripEmpty(vi),
        zh
    }
})

export function getLocale() {
    return i18n.global.locale
}

export function setLocale(locale) {
    const next = locale === 'zh' ? 'zh' : 'vi'
    i18n.global.locale = next
    Cache.set(LOCALE, next)
    return next
}

export default i18n
