<template>
  <div class="user-coupons">
    <el-tabs v-model="active" @tab-click="getMyCoupons">
      <el-tab-pane
        v-for="(item, index) in coupons"
        :label="item.title"
        :key="index"
      >
        <coupons-list
          v-if="item.hasData"
          :list="item.list"
          :type="item.type"
        />
        <null-data
          v-else
          :img="couponNull"
          :text="$t('暂无优惠券~')"
        ></null-data>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script>
import couponNull from '~/assets/images/coupon_null.png'
import { useAppStore } from "~/stores/app";
export default {
  async setup() {
    setPageLayout('user_lauout')
    const store = useAppStore();
    const { $get } = useNuxtApp();
    useHead({
      title: computed(() => store.headTitle),
      link: [{ rel: "icon", type: "image/x-icon", href: computed(() => store.favicon) }],
    });
    let { data } = await $get("coupon/myCoupon", {
      params: { type: 0 },
    });
    return {
      couponsList: data,
      couponNull,
    };
  },
  components: {},
  data() {
    return {
      active: 0,
      coupons: [
        {
          title: this.$t('可使用'),
          type: 0,
          list: [],
          hasData: true
        },
        {
          title: this.$t('已使用'),
          type: 1,
          list: [],
          hasData: true
        },
        {
          title: this.$t('已过期'),
          type: 2,
          list: [],
          hasData: true
        },
      ],
    };
  },
  methods: {
    async getMyCoupons() {
      let { data, code } = await this.$get("coupon/myCoupon", {
        params: { type: this.active },
      });
      if (code == 1) {
        this.changeData(data)
      }
    },
    changeData(data) {
      this.coupons.some((item) => {
        if (item.type == this.active) {
          Object.assign(item, { list: data, hasData: data.length });
          return true;
        }
      });
    },
  },
  watch: {
    couponsList: {
      immediate: true,
      handler(val) {
        this.changeData(val)
      },
    },
  },
};
</script>

<style lang="scss">
</style>