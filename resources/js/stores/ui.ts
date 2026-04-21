import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
  state: () => ({
    sidebarExpanded: false
  }),
  actions: {
    toggleSidebar() {
      this.sidebarExpanded = !this.sidebarExpanded
    }
  }
})
