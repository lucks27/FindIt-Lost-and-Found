<template>
    <div id="admin-app">
        <router-view />
    </div>
</template>

<script>
export default {
    name: 'AdminApp',
};
</script>

<style scoped>
#admin-app {
    width: 100%;
    height: 100vh;
    overflow: hidden;
}
</style>
