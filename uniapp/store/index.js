import { createStore } from "vuex";
import modules from "./modules";
import getters from "./getters";

const debug = process.env.NODE_ENV !== "production";

export default createStore({
  modules,
  getters,
  strict: debug
});
