<template>
  <div class="content">
    <div v-html="content"></div>
  </div>
</template>

<script>
import { useAppStore } from "~/stores/app";
export default {
  async setup() {
    setPageLayout('void')
    const store = useAppStore();
    const route = useRoute();
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
    let content;
    if (route.query.type == 1) {
      const { data } = await $get("policy/service");
      content = data;
    } else if (route.query.type == 2) {
      const { data } = await $get("policy/privacy");
      content = data;
    }
    return { content };
  },
};
</script>
<style lang="scss">
.content {
  padding: 30px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: #ffffff;
}
</style>
