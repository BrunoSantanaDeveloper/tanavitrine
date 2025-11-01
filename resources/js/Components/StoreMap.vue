<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'
import { Icon } from '@iconify/vue'

// Fix for default marker icon issue in Leaflet with bundlers
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
})

const props = defineProps({
  stores: {
    type: Array,
    required: true,
  },
  hoveredStoreId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['marker-click', 'marker-hover'])

// Map configuration
const zoom = ref(6)
const center = ref([-14.235, -51.9253]) // Center of Brazil

// Calculate map center based on stores with coordinates
const calculateCenter = () => {
  const storesWithCoords = validStores.value
  if (storesWithCoords.length === 0) {
    return [-14.235, -51.9253] // Default to Brazil center
  }

  const latSum = storesWithCoords.reduce((sum, store) => sum + parseFloat(store.latitude), 0)
  const lngSum = storesWithCoords.reduce((sum, store) => sum + parseFloat(store.longitude), 0)

  return [
    latSum / storesWithCoords.length,
    lngSum / storesWithCoords.length,
  ]
}

// Filter stores that have valid coordinates
const validStores = computed(() => {
  return props.stores.filter(store =>
    store.latitude &&
    store.longitude &&
    !Number.isNaN(parseFloat(store.latitude)) &&
    !Number.isNaN(parseFloat(store.longitude))
  )
})

// Update center when stores change
watch(() => props.stores, () => {
  if (validStores.value.length > 0) {
    center.value = calculateCenter()
    // Adjust zoom based on number of stores
    if (validStores.value.length === 1) {
      zoom.value = 13
    } else if (validStores.value.length <= 5) {
      zoom.value = 10
    } else {
      zoom.value = 6
    }
  }
}, { immediate: true })

// Create custom icon for featured stores
const createCustomIcon = (isFeatured = false) => {
  const color = isFeatured ? '#FFD700' : '#3B82F6'
  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div class="relative">
        <div class="absolute -translate-x-1/2 -translate-y-full">
          <svg width="32" height="40" viewBox="0 0 32 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 0C7.163 0 0 7.163 0 16C0 27 16 40 16 40C16 40 32 27 32 16C32 7.163 24.837 0 16 0Z" fill="${color}"/>
            <circle cx="16" cy="15" r="6" fill="white"/>
          </svg>
        </div>
      </div>
    `,
    iconSize: [32, 40],
    iconAnchor: [16, 40],
    popupAnchor: [0, -40],
  })
}

// Handle marker click
function handleMarkerClick(store) {
  emit('marker-click', store)
}

// Handle marker mouseover
function handleMarkerHover(store) {
  emit('marker-hover', store)
}

// Map ref for accessing Leaflet instance
const mapRef = ref(null)

// Watch for hovered store to highlight marker
watch(() => props.hoveredStoreId, (newId) => {
  if (newId && mapRef.value) {
    const store = validStores.value.find(s => s.id === newId)
    if (store) {
      // Optionally pan to the hovered store
      // mapRef.value.leafletObject.panTo([parseFloat(store.latitude), parseFloat(store.longitude)])
    }
  }
})

// Function to invalidate map size (useful when map is shown/hidden)
function invalidateMapSize() {
  nextTick(() => {
    if (mapRef.value?.leafletObject) {
      mapRef.value.leafletObject.invalidateSize()
    }
  })
}

// Expose function for parent components
defineExpose({
  invalidateMapSize,
})

onMounted(() => {
  // Ensure map renders correctly
  invalidateMapSize()
})

// Re-invalidate when validStores changes
watch(() => validStores.value.length, () => {
  setTimeout(invalidateMapSize, 100)
})
</script>

<template>
  <div class="w-full h-full relative">
    <LMap
      ref="mapRef"
      v-model:zoom="zoom"
      v-model:center="center"
      :use-global-leaflet="false"
      class="w-full h-full rounded-lg"
    >
      <LTileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
      />

      <!-- Markers for each store -->
      <LMarker
        v-for="store in validStores"
        :key="store.id"
        :lat-lng="[parseFloat(store.latitude), parseFloat(store.longitude)]"
        :icon="createCustomIcon(store.featured)"
        @click="handleMarkerClick(store)"
        @mouseover="handleMarkerHover(store)"
      >
        <LPopup :options="{ maxWidth: 220, minWidth: 180 }">
          <div class="w-[180px] py-4 -mt-3">
            <!-- Store Image/Logo -->
            <div v-if="store.logo || store.image" class="relative">
              <div class="w-full aspect-square bg-white rounded-lg overflow-hidden border border-gray-200 flex items-center justify-center">
                <img
                  :src="store.logo || store.image"
                  :alt="store.name"
                  class="w-full h-full object-contain p-2"
                >
              </div>
              <!-- Featured Badge -->
              <div
                v-if="store.featured"
                class="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg flex items-center gap-1"
              >
                <Icon icon="lucide:star" class="size-3" />
                Destaque
              </div>
            </div>

            <!-- Store Info -->
            <div class="space-y-2">
              <!-- Store Name -->
              <h3 class="font-bold text-base text-gray-900 leading-tight">
                {{ store.name }}
              </h3>

              <!-- Store Code -->
              <div class="flex items-center gap-1 text-xs text-gray-500">
                <Icon icon="lucide:hash" class="size-3" />
                <span>{{ store.code }}</span>
              </div>

              <!-- Category -->
              <div v-if="store.category" class="flex items-center gap-1.5 text-sm text-gray-700">
                <Icon icon="lucide:tag" class="size-4 text-teal-600" />
                <span>{{ store.category }}</span>
              </div>

              <!-- Location -->
              <div v-if="store.location" class="flex items-center gap-1.5 text-sm text-gray-700">
                <Icon icon="lucide:map-pin" class="size-4 text-red-500" />
                <span>{{ store.location }}</span>
              </div>

              <!-- Description Preview -->
              <p v-if="store.description" class="text-xs text-gray-600 line-clamp-2">
                {{ store.description }}
              </p>

              <!-- Divider -->
              <div class="border-t border-gray-200 my-3" />

              <!-- Action Button -->
              <a
                :href="store.url"
                style="color:#fff"
                class="block w-full text-center bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors shadow-sm"
                _target
              >
                Ver Vitrine
              </a>
            </div>
          </div>
        </LPopup>
      </LMarker>
    </LMap>

    <!-- No coordinates warning -->
    <div
      v-if="validStores.length === 0"
      class="absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg"
    >
      <div class="text-center p-6">
        <Icon icon="lucide:map-pin-off" class="size-12 text-gray-400 mx-auto mb-3" />
        <p class="text-gray-600 font-medium">Nenhuma loja com localização disponível</p>
        <p class="text-sm text-gray-500 mt-1">
          As coordenadas de localização ainda não foram cadastradas.
        </p>
      </div>
    </div>

    <!-- Store count badge -->
    <div
      v-if="validStores.length > 0"
      class="absolute top-4 left-4 bg-white rounded-lg shadow-lg px-3 py-2 z-[1000]"
    >
      <div class="flex items-center gap-2">
        <Icon icon="lucide:map-pin" class="size-4 text-primary" />
        <span class="text-sm font-medium">
          {{ validStores.length }} {{ validStores.length === 1 ? 'loja' : 'lojas' }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.leaflet-container) {
  font-family: inherit;
}

:deep(.custom-marker) {
  background: none;
  border: none;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
  padding: 0;
}

:deep(.leaflet-popup-content) {
  margin: 12px;
  line-height: 1.4;
}
</style>
