<template>
  <div class="index">
    <div class="banner">
      <client-only>
        <!-- banner cu dung swiper, da chuyen sang el-carousel -->
        <el-carousel height="440px" autoplay>
          <el-carousel-item
            v-for="(item, index) in bannerList"
            :key="index"
            class="swiper-item"
          >
            <ad-item :item="item" />
          </el-carousel-item>
        </el-carousel>
      </client-only>
    </div>
    <activity-area
      :title="$t('热销榜单')"
      :desc="$t('实时更新热销火爆商品')"
      url="/goods_list/1"
      :list="indexData.host_list"
      :banner="indexData.host_ad"
    />
    <activity-area
      :title="$t('新品推荐')"
      :desc="$t('品质好货 件件是精品')"
      url="/goods_list/2"
      :list="indexData.new_list"
      :banner="indexData.new_ad"
    />
    <activity-area
      :title="$t('好物优选')"
      :desc="$t('精挑细选 挑你喜欢')"
      url="/goods_list/3"
      :list="indexData.best_list"
      :banner="indexData.best_ad"
    />
    <div
      class="category-list"
      v-for="(item, index) in indexData.category_list"
      :key="index"
    >
      <div class="category-hd row">
        <div class="title bold line1">F{{ index + 1 }} / {{ item.name }}</div>
        <div class="row category-two flex1 wrap">
          <nuxt-link
            :to="`/category?id=${sitem.id}`"
            class="item muted mt8"
            v-for="(sitem, sindex) in item.sons"
            :key="sindex"
          >
            {{ sitem.name }}
          </nuxt-link>
        </div>
        <nuxt-link :to="`/category?id=${item.id}`" class="more"
          >{{ $t('更多') }} <el-icon><ArrowRight /></el-icon
        ></nuxt-link>
      </div>
      <div class="category-con row">
        <div class="flexnone mr16 ad-img" v-if="item.ad.image">
          <ad-item :item="item.ad"></ad-item>
        </div>
        <goods-list
          :list="item.goods_list"
          :num="item.ad.image ? 4 : 5"
        ></goods-list>
      </div>
    </div>
    <div class="coupon-popup">
      <el-dialog
        :title="$t('新人送券')"
        v-model="showCoupon"
        width="700px"
        center
        top="30vh"
      >
        <div class="column-center black">
          <div class="coupons-list row">
            <template v-for="(item, index) in couponList" :key="index">
              <div class="item">
                <div :class="['info white']">
                  <div class="info-hd row">
                    <div>
                      <price-formate
                        :price="item.money"
                        :first-size="38"
                        :second-size="38"
                      />
                    </div>
                    <div class="ml8 flex1">
                      <div class="line1">{{ item.name }}</div>
                      <div class="xs line1">{{ item.use_goods_type }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
        <template #footer>
          <div class="dialog-footer">
            <el-button
              style="width:200px"
              type="primary"
              @click="showCoupon = false"
              >{{ $t('确定') }}</el-button
            >
          </div>
        </template>
      </el-dialog>
    </div>
    <!-- 服务协议 -->
    <server-explan></server-explan>
  </div>
</template>

<script>
import ActivityArea from "~/components/activityArea";
import { ArrowRight } from "@element-plus/icons-vue";
import { paramsToStr } from "~/utils/tools";
import ServerExplan from "../components/server-explan.vue";
import { useAppStore } from "~/stores/app";
export default {
  async setup() {
    const store = useAppStore();
    const { $get } = useNuxtApp();
    const { clarity_app_id } = store.getSiteStatistics || {};
    if (clarity_app_id) {
      (function(c, l, a, r, i, t, y) {
        c[a] =
          c[a] ||
          function() {
            (c[a].q = c[a].q || []).push(arguments);
          };
        t = l.createElement(r);
        t.async = 1;
        t.src = "https://www.clarity.ms/tag/" + i;
        y = l.getElementsByTagName(r)[0];
        y.parentNode.insertBefore(t, y);
      })(window, document, "clarity", "script", clarity_app_id);
    }
    useHead({
      title: computed(() => store.headTitle),
      link: [
        {
          rel: "icon",
          type: "image/x-icon",
          href: computed(() => store.favicon)
        }
      ]
    });
    const banner = $get("ad_content/lists", { params: { pid: 2, client: 2 } });
    const index = $get("pc/index");
    const { data: bannerList } = await banner;
    const { data: indexData } = await index;
    let couponList = [];
    if (store.isLogin) {
      const { data } = await $get("coupon/registerSendCoupon");
      couponList = data;
    }
    return { bannerList, indexData, couponList };
  },
  components: {
    ActivityArea,
    ArrowRight
  },
  data() {
    return {
      showCoupon: false
    };
  },
  watch: {
    couponList: {
      immediate: true,
      handler(val) {
        if (val.length) {
          this.showCoupon = true;
          useAppStore().getPublicData();
        }
      }
    }
  }
};
</script>

<style lang="scss">
.index {
  .banner {
    margin-left: 160px;
    margin-bottom: 16px;
    height: 440px;
    .swiper-container {
      --swiper-navigation-size: 20px;
      --swiper-pagination-color: #fff;
    }
    .swiper-item {
      height: 440px;
      a {
        display: inline-block;
        width: 100%;
        height: 100%;
      }
    }
  }
  .category-list {
    .category-hd {
      height: 79px;
      .title {
        font-size: 28px;
      }
      .category-two {
        margin-left: 24px;
        .item {
          margin-right: 24px;
          &:hover {
            color: #333;
          }
        }
      }
    }
    .category-con {
      align-items: flex-start;
      & > .ad-img {
        width: 224px;
        height: 636px;
      }
    }
  }
  .coupons-list {
    padding: 0 18px;
    flex-wrap: wrap;
    position: relative;
    .item {
      margin-bottom: 20px;
      margin-right: 16px;
      position: relative;
      cursor: pointer;
      .info {
        padding: 0 10px;
        background: url("~/assets/images/bg_coupon_s.png") no-repeat;
        width: 240px;
        height: 80px;
        background-size: 100%;
      }
    }
  }
}
</style>
