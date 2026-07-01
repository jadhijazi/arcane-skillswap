<template>
  <section class="panel">
    <div class="section-header">
      <div>
        <p class="eyebrow">Tutors</p>
        <h2>Find a tutor</h2>
      </div>
    </div>

    <div class="card stack-sm">
<<<<<<< HEAD
      <input v-model="query" type="text" placeholder="Search tutor name…" />
      <div class="row-between" style="flex-wrap: wrap; gap: 10px;">
        <select v-model="selectedCategory" @change="onCategoryChange">
          <option value="">All subjects</option>
          <option v-for="c in store.categories" :key="c">{{ c }}</option>
        </select>

        <select v-model="selectedSkillId" :disabled="!selectedCategory" @change="search">
          <option :value="null">{{ selectedCategory ? 'All skills' : 'Pick a subject first' }}</option>
          <option v-for="s in skillsForCategory" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>

        <select v-model="faculty" @change="search">
          <option value="">All faculties</option>
          <option v-for="f in faculties" :key="f">{{ f }}</option>
        </select>

        <label style="flex-direction: row; align-items: center; gap: 8px;">
          Max RM/hr
          <input v-model.number="maxPrice" type="number" min="5" max="200" style="width: 70px;" @change="search" />
        </label>

        <label style="flex-direction: row; align-items: center; gap: 8px;">
          Min rating
          <input v-model.number="minRating" type="number" min="0" max="5" step="0.1" style="width: 60px;" @change="search" />
=======
      <input v-model="query" type="text" placeholder="Search skill, tutor name..." />
      <div class="row-between" style="flex-wrap: wrap; gap: 10px;">
        <select v-model="category">
          <option value="All">All subjects</option>
          <option v-for="c in store.skillCategories" :key="c">{{ c }}</option>
        </select>
        <select v-model="faculty">
          <option v-for="f in store.faculties" :key="f">{{ f }}</option>
        </select>
        <label style="flex-direction: row; align-items: center; gap: 8px;">
          Max RM/hr
          <input v-model.number="maxPrice" type="number" min="5" max="40" style="width: 70px;" />
        </label>
        <label style="flex-direction: row; align-items: center; gap: 8px;">
          Min rating
          <input v-model.number="minRating" type="number" min="0" max="5" step="0.1" style="width: 60px;" />
>>>>>>> 7c321622ee66cec5fb481fd1211ccd891abaf80a
        </label>
      </div>
    </div>

<<<<<<< HEAD
    <div v-if="store.status === 'loading'" class="card empty-state">
      <p class="muted">Searching for tutors…</p>
    </div>

    <template v-else>
      <p class="muted">{{ selectedSkillId ? `${results.length} tutor${results.length === 1 ? '' : 's'} found` : 'Select a skill to search' }}</p>

      <div v-if="results.length" class="grid-cards">
        <article v-for="t in results" :key="t.id" class="card tutor-card stack-sm">
          <div class="row-between" style="align-items: flex-start;">
            <div>
              <h3>{{ t.name }}</h3>
              <p class="faint">{{ t.faculty }}</p>
            </div>
          </div>
          <p class="muted">{{ t.skillName }}</p>
          <div class="row-between">
            <span class="faint">⭐ {{ t.avgRating.toFixed(1) }} ({{ t.totalSessions }} sessions)</span>
            <strong>RM {{ t.rate }}/hr</strong>
          </div>
          <div class="row-between" style="gap: 8px;">
            <RouterLink :to="`/messages/${t.userId}`" class="button ghost" style="flex: 1;">Message</RouterLink>
            <RouterLink :to="`/booking/${t.id}`" class="button solid" style="flex: 1;">Book session</RouterLink>
          </div>
        </article>
      </div>

      <div v-else-if="selectedSkillId && store.status !== 'loading'" class="card empty-state">
        <h3>No tutors match those filters</h3>
        <p>Try widening your price range or clearing the search term.</p>
      </div>
    </template>

    <p v-if="store.error" class="faint" style="color: var(--red, #e05);">{{ store.error }}</p>
=======
    <p class="muted">Showing {{ results.length }} tutor{{ results.length === 1 ? '' : 's' }}</p>

    <div v-if="results.length" class="grid-cards">
      <article v-for="t in results" :key="t.id" class="card tutor-card stack-sm">
        <div class="row-between" style="align-items: flex-start;">
          <div>
            <h3>{{ t.name }}</h3>
            <p class="faint">{{ t.year }}</p>
          </div>
          <span v-if="t.verified" class="pill verified">✓ Verified</span>
        </div>
        <p class="muted">{{ t.bio }}</p>
        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
          <span v-for="s in t.skills" :key="s" class="pill skill">{{ s }}</span>
        </div>
        <div class="row-between">
          <span class="faint">⭐ {{ t.rating }} ({{ t.reviews }} reviews)</span>
          <strong>RM {{ t.rate }}/hr</strong>
        </div>
        <div class="row-between" style="gap: 8px;">
          <RouterLink :to="`/messages/${t.id}`" class="button ghost" style="flex: 1;">Message</RouterLink>
          <RouterLink :to="`/booking/${t.id}`" class="button solid" style="flex: 1;">Book session</RouterLink>
        </div>
      </article>
    </div>
    <div v-else class="card empty-state">
      <h3>No tutors match those filters</h3>
      <p>Try widening your price range or clearing the search term.</p>
    </div>
>>>>>>> 7c321622ee66cec5fb481fd1211ccd891abaf80a
  </section>
</template>

<script setup lang="ts">
<<<<<<< HEAD
import { ref, computed, onMounted } from 'vue'
import { useTutorStore } from '@/stores/useTutorStore'

const store = useTutorStore()

const query = ref('')
const selectedCategory = ref('')
const selectedSkillId = ref<number | null>(null)
const faculty = ref('')
const maxPrice = ref(200)
const minRating = ref(0)

const faculties = ['Computing', 'Engineering', 'Business', 'Science', 'Medicine', 'Law', 'Arts']

const skillsForCategory = computed(() =>
  selectedCategory.value ? store.skillsInCategory(selectedCategory.value) : []
)

const results = computed(() => {
  const q = query.value.toLowerCase()
  return store.tutors.filter((t) => {
    if (q && !`${t.name} ${t.skillName}`.toLowerCase().includes(q)) return false
    return true
  })
})

function onCategoryChange() {
  selectedSkillId.value = null
  store.tutors = []
}

async function search() {
  if (!selectedSkillId.value) return
  await store.searchTutors({
    skillId: selectedSkillId.value,
    faculty: faculty.value || undefined,
    minRating: minRating.value || undefined,
    maxPrice: maxPrice.value || undefined,
    query: query.value || undefined,
  })
}

onMounted(() => store.loadSkills())
=======
import { ref, computed } from 'vue'
import { useAppStore } from '@/stores/useAppStore'

const store = useAppStore()

const query = ref('')
const category = ref('All')
const faculty = ref('All faculties')
const maxPrice = ref(40)
const minRating = ref(0)

const results = computed(() =>
  store
    .filteredTutors({
      query: query.value,
      category: category.value,
      faculty: faculty.value,
      maxPrice: maxPrice.value,
      minRating: minRating.value,
    })
    .sort((a, b) => b.rating - a.rating),
)
>>>>>>> 7c321622ee66cec5fb481fd1211ccd891abaf80a
</script>
