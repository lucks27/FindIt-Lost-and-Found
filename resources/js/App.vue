<template>
    <main class="content">
        <router-view v-slot="{ Component, route }">
            <div :key="route.name">
                <component :is="Component" />
            </div>
        </router-view>
    </main>
</template>

<script setup></script>
