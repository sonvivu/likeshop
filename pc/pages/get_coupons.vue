<template>
  <div class="get-coupons">
    <img
      style="width: 100%"
      src="~/assets/images/bg_coupon_center.png"
      alt=""
    />
    <div class="coupons bg-white">
      <div class="title bold">{{ $t('每日领券') }}</div>
      <div class="list">
        <coupons-list
          v-if="couponList.length"
          :list="couponList"
          :type="3"
          @reflash="getCouponsList"
        />
        <null-data
          v-else
          :img="couponNull"
          :text="$t('暂无优惠券~')"
        ></null-data>
      </div>
    </div>
  </div>
</template>

<script>
import { useAppStore } from "~/stores/app";
import couponNull from "~/assets/images/coupon_null.png";
export default {
  async setup() {
    const store = useAppStore();
    const route = useRoute();
    const { $get } = useNuxtApp();
    useHead({
      title: computed(() => store.headTitle),
      link: [{ rel: "icon", type: "image/x-icon", href: computed(() => store.favicon) }],
    });
    const { data } = await $get("coupon/couponList", {
      params: { id: route.query.id },
    });
    return {
      couponList: data,
    };
  },
  methods: {
    async getCouponsList() {
      const { data, code } = await this.$get("coupon/couponList");
      if (code == 1) {
        this.couponList = data;
      }
    },
  },
};
</script>

<style lang="scss">
.get-coupons {
  padding: 16px 0;
  .coupons {
    padding: 0 14px;
    .title {
      padding: 16px 18px;
      font-size: 20px;
    }
  }
}
</style>
