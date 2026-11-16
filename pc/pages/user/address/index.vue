<template>
  <div class="address-list">
    <div class="address-header lg">{{ $t('收货地址') }}</div>
    <div class="address-content" v-show="!isDataNull">
      <el-table :data="addressList" style="width: 100%">
        <el-table-column
          prop="contact"
          :label="$t('收货人')"
          width="200"
        />
        <el-table-column
          prop="telephone"
          :label="$t('手机号码')"
          width="150"
        />
        <el-table-column
          prop="addressDetail"
          :label="$t('收货地区')"
        />
         <el-table-column
          fixed="right"
          :label="$t('操作')"
          width="216">
          <template #default="scope">
            <div class="row">
              <div style="margin-right: 24px;">
                <el-button @click="modifyRow(scope.row)" link class="nr" size="small">{{ $t('修改') }}</el-button>
              </div>
              <el-popconfirm
                :confirm-button-text="$t('确定')"
                :cancel-button-text="$t('取消')"
                :icon="InfoFilled"
                icon-color="red"
                :title="$t('确定要删除地址吗？')"
                @confirm="deleteAddress(scope.row)"
              >
                <template #reference>
                  <el-button link size="small" class="nr">{{ $t('删除') }}</el-button>
                </template>
              </el-popconfirm>
              <div class="row" style="margin-left: 36px;cursor: pointer;" @click="setDefault(scope.row)">
                <div :class="{'default-border': scope.row.is_default == 0}" style="width: 16px;height: 16px;margin-right: 8px;" v-if="scope.row.is_default == 0" />
                <img :class="{'default-border': scope.row.is_default == 0}" style="width: 16px;height: 16px;margin-right: 8px;" v-else src="~/assets/images/logistics_success.png"/>
                <div class="nr" :class="{'primary': scope.row.is_default == 1}">{{scope.row.is_default == 1 ? $t('默认') : $t('设为默认')}}</div>
                <!-- <el-radio v-model="scope.row.is_default" >{{scope.row.is_default == 1 ? '默认' : '设为默认'}}</el-radio> logistics_success -->
              </div>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <div class="add-address-btn row-end">
        <el-button type="primary" style="width: 106px" @click="showAddressPop">{{ $t('添加地址') }}</el-button>
      </div>
    </div>
    <div class="column-center data-null" v-show="isDataNull">
      <img style="width: 150px;height: 150px" src="@/assets/images/address_null.png" />
      <div class="muted xs mt8">{{ $t('暂无地址记录～') }}</div>
      <div class="mt8">
        <el-button type="primary" style="width: 106px" @click="showAddressPop">{{ $t('添加地址') }}</el-button>
      </div>
    </div>
    <address-add v-model="addressPop" :aid="editId == -1 ? '' : editId" @success="getAddressList" />
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
    let addressList = [];
    let isDataNull = true;
    let res = await $get('user_address/lists');
    if(res.code == 1) {
      addressList = res.data;
      addressList.forEach(item => {
        item.addressDetail = item.province + ' ' + item.city + ' ' + item.district + ' ' + item.address;
      })
      if(addressList.length <= 0) {
        isDataNull = true
      }
      else {
        isDataNull = false
      }
    }
    return {
      addressList,
      isDataNull,
    }
  },
  components: {
    InfoFilled
  },
  data() {
    return {
      addressPop: false,
      editId: -1
    }
  },
  mounted() {
 
  },
  methods: {
    modifyRow(e) {
      this.editId = e.id;
      this.addressPop = true
    },
    async setDefault(e) {
      let res = await this.$post('user_address/setdefault', {id: e.id});
      if(res.code == 1) {
        await this.getAddressList()
        this.$message({
          message: this.$t('设置成功'),
          type: 'success'
        })
      }
    },
    async getAddressList() {
      let res = await this.$get('user_address/lists');
      if(res.code == 1) {
        this.addressList = res.data;
        this.addressList.forEach(item => {
          item.addressDetail = item.province + ' ' + item.city + ' ' + item.district + ' ' + item.address;
        })
        if(this.addressList.length <= 0) {
          this.isDataNull = true
        }
        else {
          this.isDataNull = false
        }
      }
    },
    async deleteAddress(e) {
      let res = await this.$post('user_address/del', {id: e.id});
      if(res.code == 1) {
        await this.getAddressList();
        this.$message({
          message: this.$t('删除成功'),
          type: 'success'
        })
      }
    },
    showAddressPop() {
      this.addressPop = true;
      this.editId = -1;
    }
  }
}
</script>

<style lang="scss" scoped>
  .address-list {
    padding: 10px;
    .address-header {
      padding: 10px 5px;
      border-bottom: 1px solid $-color-border;
    }
    .address-content {
      margin-top: 15px;
      .default-border {
        border: 1px solid $-color-border;
        border-radius: 60px;
      }
      ::v-deep .el-table {
        color: #222;
      }
      ::v-deep .el-table .el-button--text {
        color: #222;
        font-weight: 400;
      }
      ::v-deep  .el-table th {
        background-color: #F2F2F2;
      }
      ::v-deep .el-table thead {
        color: #555555;
      }
      .add-address-btn {
        margin-top: 18px;
      }
    }
    .data-null {
      padding-top: 100px;
    }
  }
  
</style>