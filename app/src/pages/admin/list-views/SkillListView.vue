<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'skills'" :model="Skill" />
    </div>
</template>

<script setup lang="ts">
import { defineComponent } from 'vue';
import ListView from '@/components/list-views/ListView.vue';
import Skill from '@/models/employees/Skill.ts';

defineComponent({
    name: 'SkillListView',
});
</script>
