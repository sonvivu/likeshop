<template>
  <div class="goods-list">
    <div class="banner mt16">
      <ad-item :item="ad" v-if="ad.image"></ad-item>
    </div>
    <div class="sort mb16 row bg-white">
      <div class="title">{{ $t('排序方式：') }}</div>
      <div class="sort-name ml16 row">
        <div
          :class="['item', { active: sortType == '' }]"
          @click="changeSortType('')"
        >
          {{ $t('综合') }}
        </div>
        <div
          :class="['item', { active: sortType == 'price' }]"
          @click="changeSortType('price')"
        >
          {{ $t('价格') }}
          <el-icon v-show="priceSort == 'desc'"><ArrowDown /></el-icon>
          <el-icon v-show="priceSort == 'asc'"><ArrowUp /></el-icon>
        </div>
        <div
          :class="['item', { active: sortType == 'sales_sum' }]"
          @click="changeSortType('sales_sum')"
        >
          {{ $t('销量') }}
          <el-icon v-show="saleSort == 'desc'"><ArrowDown /></el-icon>
          <el-icon v-show="saleSort == 'asc'"><ArrowUp /></el-icon>
        </div>
      </div>
    </div>
    <template v-if="goodsList.length">
      <goods-list :list="goodsList" />
      <div
        class="pagination row-center"
        style="padding-bottom: 38px"
        v-if="count"
      >
        <el-pagination
          background
          layout="prev, pager, next"
          :total="count"
          :prev-text="$t('上一页')"
          :next-text="$t('下一页')"
          hide-on-single-page
          :page-size="20"
          @current-change="changePage"
        >
        </el-pagination>
      </div>
    </template>
    <null-data
      v-else
      :img="goodsNull"
      :text="$t('暂无商品~')"
    ></null-data>
  </div>
</template>

<script>
import { trottle } from "~/utils/tools";
import { ArrowDown, ArrowUp } from "@element-plus/icons-vue";
import { useAppStore } from "~/stores/app";
import goodsNull from "~/assets/images/goods_null.png";
export default {
  async setup() {
    const store = useAppStore();
    const route = useRoute();
    const { $get } = useNuxtApp();
    useHead({
      title: computed(() => store.headTitle),
      link: [{ rel: "icon", type: "image/x-icon", href: computed(() => store.favicon) }],
    });
    const goodsList = ref([]);
    const count = ref(0);
    const ad = ref({});
    // Thay the watchQuery cu: route doi (type param / name query) thi nap lai.
    const load = async () => {
      const { type } = route.params;
      const { name } = route.query;
      const data = { page_size: 20, type, name };
      if (type == 1) {
        (data.sort = "desc"), (data.sort_type = "sales_sum");
      }
      const {
        data: { list, count: c },
      } = await $get("pc/goodsList", { params: data });
      const { data: indexData } = await $get("pc/index");
      let a = {};
      switch (type) {
        case "1":
          a = indexData.host_ad;
          break;
        case "2":
          a = indexData.new_ad;
          break;
        case "3":
          a = indexData.best_ad;
          break;
      }
      goodsList.value = list;
      count.value = c;
      ad.value = a;
    };
    await load();
    return { goodsList, count, ad, load };
  },
  components: {
    ArrowDown,
    ArrowUp,
  },
  watch: {
    $route() {
      this.load();
    },
  },
  data() {
    return {
      sortType: "",
      saleSort: "desc",
      priceSort: "desc",
      page: "",
    };
  },
  created() {
    this.changeSortType = trottle(this.changeSortType, 500, this);
  },
  methods: {
    changeSortType(type) {
      this.sortType = type;
      switch (type) {
        case "price":
          if (this.priceSort == "asc") {
            this.priceSort = "desc";
          } else if (this.priceSort == "desc") {
            this.priceSort = "asc";
          }
          break;
        case "sales_sum":
          if (this.saleSort == "asc") {
            this.saleSort = "desc";
          } else if (this.saleSort == "desc") {
            this.saleSort = "asc";
          }
          break;
        default:
      }
      this.getGoods();
    },
    changePage(current) {
      this.page = current;
      this.getGoods();
    },
    async getGoods() {
      const { name } = this.$route.query;
      const { priceSort, sortType, saleSort } = this;
      let sort = "";
      switch (sortType) {
        case "price":
          sort = priceSort;
          break;
        case "sales_sum":
          sort = saleSort;
          break;
      }
      const {
        data: { list, count },
      } = await this.$get("pc/goodsList", {
        params: {
          page_size: 20,
          page_no: this.page,
          sort_type: sortType,
          sort,
          name,
        },
      });
      this.goodsList = list;
    },
  },
};
</script>

<style lang="scss">
.goods-list {
  .banner {
    img {
      width: 100%;
      display: block;
    }
  }
  .sort {
    padding: 15px 16px;
    .sort-name {
      .item {
        margin-right: 30px;
        cursor: pointer;
        &.active {
          color: $-color-primary;
        }
      }
    }
  }
}
</style>