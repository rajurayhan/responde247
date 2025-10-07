<template>
  <div id="app">
    <router-view />
    <Toast ref="toast" />
  </div>
</template>

<script>
import Toast from './components/shared/Toast.vue'

export default {
  name: 'App',
  components: {
    Toast
  },
  mounted() {
    // Make toast available globally with proper initialization
    this.$nextTick(() => {
      this.$toast = this.$refs.toast
      console.log('Toast system initialized:', !!this.$toast)
    })
  }
}
</script>

<style>
#app {
  font-family: 'Inter', system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  padding: 0;
  background-color: #f8fafc;
}
</style> 