<template>
  <div class="activity-area" v-if="list.length">
    <div class="activity-header row">
      <div class="content row flex1">
        <div class="title bold mr8">{{ title }}</div>
        <div class="lg muted">{{ desc }}</div>
      </div>
      <nuxt-link :to="url" class="more"
        >{{ $t('更多') }} <el-icon><ArrowRight /></el-icon
      ></nuxt-link>
    </div>
    <div v-if="banner.image">
        <ad-item :item="banner"></ad-item>
    </div>
    <div class="activity-goods mt16">
      <goods-list :list="list" />
    </div>
  </div>
</template>

<script>
import { ArrowRight } from "@element-plus/icons-vue";
export default {
  components: { ArrowRight },
  props: {
    url: {
      type: String,
      default: "",
    },
    title: {
      type: String,
    },
    desc: {
      type: String,
    },
    banner: {
      type: Object,
      default: () => ({}),
    },
    list: {
      type: Array,
      default: () => ([]),
    },
  },
  created() {
    
  }
};
</script>

<style lang="scss" scoped>
.activity-area {
  margin-bottom: 16px;
  .activity-header {
    height: 80px;
    .title {
      font-size: 28px;
    }
  }
}
</style>