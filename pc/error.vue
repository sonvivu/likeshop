<template>
  <div class="error column-center">
    <h1 class="mb16">{{ $t('抱歉，您访问的页面出错了') }}</h1>
    <div class="muted">{{ $t('您可能输错了网址，或该网页已被删除、不存在等') }}</div>
  </div>
</template>

<style lang="scss" scoped>
.error {
  padding-top: 200px;
}
</style>