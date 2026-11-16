<template>
    <div class="category">
        <div class="category-hd bg-white">
            <div class="category-wrap">
                <div class="category-con row">
                    <div class="name muted">{{ $t('一级分类：') }}</div>
                    <div class="category-list row wrap lighter">
                        <div
                            :class="['item line1', { active: oneIndex == index }]"
                            v-for="(item, index) in categoryOne"
                            :key="index"
                            @click="changeData(item.id)"
                        >
                            {{ item.name }}
                        </div>
                    </div>
                </div>
                <div class="category-con row">
                    <div class="name muted">{{ $t('二级分类：') }}</div>
                    <div class="category-list row wrap lighter">
                        <div
                            :class="['item line1', { active: twoIndex === '' }]"
                            @click="clickAllTwo"
                        >
                            {{ $t('全部') }}
                        </div>
                        <div
                            :class="['item line1', { active: twoIndex === index }]"
                            v-for="(item, index) in categoryTwo"
                            :key="index"
                            @click="changeData(item.id)"
                        >
                            {{ item.name }}
                        </div>
                    </div>
                </div>
                <div class="category-con row">
                    <div class="name muted">{{ $t('三级分类：') }}</div>
                    <div class="category-list row wrap lighter">
                        <div
                            :class="['item line1', { active: threeIndex === '' }]"
                            @click="clickAll"
                        >
                            {{ $t('全部') }}
                        </div>
                        <div
                            :class="['item line1', { active: threeIndex === index }]"
                            v-for="(item, index) in categoryThree"
                            :key="index"
                            @click="changeData(item.id)"
                        >
                            {{ item.name }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="sort mb16 row bg-white">
                <div class="title muted">{{ $t('排序方式：') }}</div>
                <div class="sort-name ml16 row lighter">
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
                        <el-icon v-show="priceSort == 'desc'"
                          ><ArrowDown
                        /></el-icon>
                        <el-icon v-show="priceSort == 'asc'"
                          ><ArrowUp
                        /></el-icon>
                    </div>
                    <div
                        :class="['item', { active: sortType == 'sales_sum' }]"
                        @click="changeSortType('sales_sum')"
                    >
                        {{ $t('销量') }}
                        <el-icon v-show="saleSort == 'desc'"
                          ><ArrowDown
                        /></el-icon>
                        <el-icon v-show="saleSort == 'asc'"
                          ><ArrowUp
                        /></el-icon>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isHasGoods">
            <goods-list :list="goodsList" />
            <div
                class="pagination row-center"
                style="padding-bottom: 38px"
                v-if="count"
            >
                <el-pagination
                    background
                    hide-on-single-page
                    layout="prev, pager, next"
                    :total="count"
                    :prev-text="$t('上一页')"
                    :next-text="$t('下一页')"
                    :page-size="20"
                    @current-change="changePage"
                >
                </el-pagination>
            </div>
        </div>
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
    const { $get } = useNuxtApp();
    useHead({
      title: computed(() => store.headTitle),
      link: [
        {
          rel: "icon",
          type: "image/x-icon",
          href: computed(() => store.favicon),
        },
      ],
    });
    const { data } = await $get("goods_category/lists", {
      params: { client: 2 },
    });
    return {
      categoryList: data,
    };
  },
  components: {
    ArrowDown,
    ArrowUp,
  },
  data() {
    return {
      count: 0,
      oneIndex: 0,
      twoIndex: "",
      threeIndex: "",
      categoryOne: [],
      categoryTwo: [],
      categoryThree: [],
      sortType: "",
      saleSort: "desc",
      priceSort: "desc",
      page: "",
      goodsList: [],
      cateId: 0,
      isHasGoods: true,
    };
  },
  created() {
    this.changeSortType = trottle(this.changeSortType, 500, this);
  },
  methods: {
    changeData(id) {
       const { categoryList } = this;
      this.setIndex(id)
      this.categoryOne = categoryList;
      this.categoryTwo = categoryList[this.oneIndex] ? categoryList[this.oneIndex].sons : [];

      this.categoryThree = this.categoryTwo[this.twoIndex] ? this.categoryTwo[this.twoIndex].sons : [];

      this.setCateId(id)
      this.getGoods();
    },
    setCateId(id) {
      if(this.twoIndex == '' && this.threeIndex == '' && this.oneIndex !== '') {
        this.cateId = this.categoryOne[this.oneIndex].id
      }
      if(this.threeIndex == '' && this.twoIndex !== '') {
        this.cateId = this.categoryTwo[this.twoIndex].id
      }
      if(id) {
        this.cateId = id;
      }
    },
    setIndex(id) {
       const { categoryList } = this;
        categoryList.some((oitem, oindex) => {
          if (oitem.id === id) {
            this.oneIndex = oindex;
            this.twoIndex = "";
            this.threeIndex = "";
            return true;
          }
          return oitem.sons && oitem.sons.some((witem, windex) => {
            if (witem.id === id) {
              this.oneIndex = oindex;
              this.twoIndex = windex;
              this.threeIndex = "";
              return true;
            }
            return witem.sons && witem.sons.some((titem, tindex) => {
              if (titem.id === id) {
                this.oneIndex = oindex;
                this.twoIndex = windex;
                this.threeIndex = tindex;
                return true;
              }
            });
          });
        });
    },
    clickAllTwo() {
      this.twoIndex=""
      this.threeIndex = "";
      this.changeData();
    },
    clickAll() {
      this.threeIndex = "";
      this.changeData();
    },
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
      const {
        priceSort,
        sortType,
        saleSort,
      } = this;
      let category_id = this.cateId;
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
          category_id,
        },
      });
      this.goodsList = list;
      if (!list.length) {
        this.isHasGoods = false;
      } else {
        this.isHasGoods = true;
      }
      this.count = count;
    },
  },
  watch: {
    '$route.query.id'(id) {
      this.changeData(Number(id));
    },
    categoryList: {
      immediate: true,
      handler(value) {
        const { id } = this.$route.query;
        this.changeData(Number(id));
      },
    },
  },
};
</script>

<style lang="scss" scoped>
.category {
    padding: 16px 0;
    .category-hd {
        .category-wrap {
            padding: 0 16px;
        }
        .category-con {
            border-bottom: 1px dashed #e5e5e5;
            align-items: flex-start;
            padding-top: 16px;

            .name {
                flex: none;
            }
            .item {
                margin-bottom: 16px;
                width: 84px;
                margin-left: 14px;
                cursor: pointer;
                &.active {
                    color: $-color-primary;
                }
                &:hover {
                    color: $-color-primary;
                }
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
}
</style>