<template>
  <aside class="aside-nav bg-white">
    <div class="nav">
      <div class="title lg">{{ $t('订单管理') }}</div>
      <ul>
        <li>
          <nuxt-link to="/user/order">{{ $t('我的订单') }}</nuxt-link>
        </li>
        <li>
          <nuxt-link to="/user/evaluation">{{ $t('评价中心') }}</nuxt-link>
        </li>
        <li>
          <nuxt-link to="/user/after_sales">{{ $t('退款/售后') }}</nuxt-link>
        </li>
        <li>
          <nuxt-link to="/user/coupons">{{ $t('优惠券') }} ({{publicData.coupon_num}})</nuxt-link>
        </li>
      </ul>
    </div>
    <div class="nav">
      <div class="title lg">{{ $t('我的服务') }}</div>
      <ul>
        <li>
          <nuxt-link to="/user/user_wallet">{{ $t('我的钱包') }}</nuxt-link>
        </li>
        <li>
          <nuxt-link to="/user/collection">{{ $t('我的收藏') }}</nuxt-link>
        </li>
        <li>
          <nuxt-link to="/user/address">{{ $t('地址管理') }}</nuxt-link>
        </li>
        <li>
          <nuxt-link to="/user/profile">{{ $t('个人资料') }}</nuxt-link>
        </li>
      </ul>
    </div>
  </aside>
</template>

<script>
import { mapState } from "pinia";
import { useAppStore } from "~/stores/app";
export default {
  created() {},
  computed: {
    ...mapState(useAppStore, ["publicData"]),
  },
};
</script>

<style lang="scss" scoped>
.aside-nav {
  width: 200px;
  min-height: 680px;
  padding: 20px 0;
  box-sizing: border-box;
  .nav {
    .title {
      padding: 14px 32px;
      font-weight: 500;
    }
    ul {
      li {
        a {
          display: block;
          padding: 10px 32px;
          &.nuxt-link-active {
            position: relative;
            color: $-color-primary;
            background-color: #ffeeef;
            &::before {
              position: absolute;
              content: "";
              display: block;
              width: 2px;
              height: 30px;
              left: 0;
              top: 50%;
              transform: translateY(-50%);
              background-color: $-color-primary;
            }
          }
        }
      }
    }
  }
}
</style>
