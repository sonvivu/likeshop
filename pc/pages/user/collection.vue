<template>
  <div class="user-collection">
    <div class="collection-header lg">
      {{ $t('收藏') }}
    </div>
    <div class="collection-list mt10" v-show="goodsCount > 0">
      <div>
        <div class="goods-item row-between" v-for="(item, index) in collectList" :key="index">
          <div style="display: flex;flex-direction: row;">
            <el-image style="width: 72px; height: 72px" fit="cover" :src="item.image" lazy />
            <div class="goods-info">
              <div class="goods-name line2">{{item.name}}</div>
              <div class="primary nr" style="font-weight: 500;">¥{{item.price}}</div>
            </div>
          </div>
          <div class="row">
            <nuxt-link class="to-buy-btn primary row-center" :to="'/goods_details/'+item.id">{{ $t('去购买') }}</nuxt-link>
             <el-popconfirm
              :title="$t('确定删除该收藏吗？')"
              :confirm-button-text="$t('确定')"
              :cancel-button-text="$t('取消')"
              :icon="InfoFilled"
              icon-color="red"
              @confirm="cancelCollection(item.id)"
            >
              <template #reference>
                <div class="cancel-btn lighter row-center">{{ $t('取消收藏') }}</div>
              </template>
            </el-popconfirm>
          </div>
        </div>
      </div>
      <div class="row-center" style="margin-top: 30px">
        <el-pagination
          hide-on-single-page
          background
          layout="prev, pager, next"
          :total="goodsCount"
          :prev-text="$t('上一页')"
          :next-text="$t('下一页')"
          :page-size="pageSize"
          @current-change="changePage"
        />
      </div>
    </div>
    <div class="column-center data-null" v-show="goodsCount <= 0">
      <img style="width: 150px;height: 150px;" src="@/assets/images/profit_null.png" />
      <div class="muted xs">{{ $t('暂无收藏商品～') }}</div>
    </div>
  </div>
</template>

<script>
import { InfoFilled } from "@element-plus/icons-vue";
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
    let collectList = [];
    let goodsCount = 0;
    let pageSize = 5
    let res = await $get("collect/getCollectGoods", {params: {page_no: 1, page_size: pageSize}});
    if(res.code == 1) {
      collectList = res.data.list;
      goodsCount = res.data.count;
    }
    return {
      collectList,
      goodsCount,
      pageSize,
    }
  },
  components: {
    InfoFilled
  },
  data() {
    return {
      currentPage: 1
    }
  },
  methods: {
    async changePage(currentPage) {
      this.currentPage = currentPage;
      let res = await this.$get("collect/getCollectGoods", {params: {page_no: currentPage, page_size: this.pageSize}});
      if(res.code == 1) {
        this.collectList = res.data.list;
        this.goodsCount = res.data.count;
      }
    },
    async cancelCollection(id) {
      let res = await this.$post('collect/handleCollectGoods', {
        is_collect: 0,
        goods_id: id
      })
      if(res.code == 1) {
        this.$message({
          message: this.$t('已取消收藏'),
          type: 'success'
        })
        this.changePage(this.currentPage)
      }
    }
  }
}
</script>

<style lang="scss">
  .user-collection {
    padding: 10px;
    .collection-header {
      padding: 10px 5px;
      border-bottom: 1px solid $-color-border;
    }
    .collection-list {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 690px;
      .goods-item {
        padding: 26px 20px;
        border-bottom: 1px dashed $-color-border;
        .goods-info {
          margin-left: 10px;
          width: 500px;
          flex: 1;
          .goods-name {
            margin-bottom: 8px;
          }
        }
        .to-buy-btn {
          border: 1px solid $-color-primary;
          height: 32px;
          width: 104px;
          font-size: 13px;
        }
        .cancel-btn {
          margin-left: 10px;
          height: 32px;
          width: 104px;
          border: 1px solid $-color-border;
          font-size: 13px;
          cursor: pointer;
        }
      }
    }
    .data-null {
      padding-top: 100px;
    }
  }
</style>