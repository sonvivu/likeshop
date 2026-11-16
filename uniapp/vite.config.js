import { defineConfig } from 'vite'
import uni from '@dcloudio/vite-plugin-uni'
// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    uni(),
  ],
  build: {
    // Vite mac dinh chi transform CJS trong node_modules; project con nhieu
    // file CJS/UMD ngoai node_modules (xtshadow-axios, jweixin, weCropper,
    // lime-painter/painter.js). Mo include ra toan bo uniapp/ thi Rollup moi
    // thay duoc default/named export cua chung. Khong co file nao vua import
    // vua module.exports nen khong so mixed-modules.
    commonjsOptions: {
      include: [/node_modules/, /uniapp\//],
    },
  },
})
