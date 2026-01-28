<template>
  <div class="broadcast-page">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Рассылка</h1>
      <p class="text-gray-600 mt-1">Отправка сообщений, фото или видео пользователям бота магазина</p>
    </div>

    <router-link
      to="/admin/shop-bot-users"
      class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
    >
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      Назад к пользователям магазина
    </router-link>

    <div v-if="!shopStore.selectedShopId" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
      <p class="text-yellow-800 font-medium">Выберите магазин</p>
      <p class="text-yellow-700 text-sm mt-1">Выберите магазин в переключателе в шапке страницы.</p>
    </div>

    <div v-else-if="!shopStore.selectedShop?.telegram_bot_token" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
      <p class="text-yellow-800 font-medium">У магазина не настроен Telegram-бот</p>
      <p class="text-yellow-700 text-sm mt-1">Настройте токен бота в карточке магазина.</p>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-6 max-w-2xl">
      <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Содержимое</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Тип сообщения</label>
          <select
            v-model="form.type"
            class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
          >
            <option value="text">Текст</option>
            <option value="photo">Фото</option>
            <option value="video">Видео</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ form.type === 'text' ? 'Текст сообщения' : 'Подпись (необязательно)' }}
          </label>
          <textarea
            v-model="form.text"
            rows="4"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900"
            :placeholder="form.type === 'text' ? 'Введите текст...' : 'Подпись к медиа'"
          ></textarea>
        </div>

        <div v-if="form.type === 'photo' || form.type === 'video'">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ form.type === 'photo' ? 'Фото' : 'Видео' }}
          </label>
          <div class="flex items-center gap-4">
            <div v-if="selectedMedia" class="relative">
              <img
                v-if="form.type === 'photo'"
                :src="selectedMedia.url"
                :alt="selectedMedia.name"
                class="h-24 w-24 object-cover rounded-lg border border-gray-300"
              />
              <div
                v-else
                class="h-24 w-24 bg-gray-100 rounded-lg border border-gray-300 flex items-center justify-center"
              >
                <span class="text-2xl">🎥</span>
              </div>
              <button
                type="button"
                @click="clearMedia"
                class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs"
              >
                ✕
              </button>
            </div>
            <button
              type="button"
              @click="showMediaSelector = true"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ selectedMedia ? 'Изменить' : 'Выбрать из медиа' }}
            </button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Получатели</h2>

        <div>
          <label class="flex items-center gap-2 cursor-pointer mb-2">
            <input
              v-model="sendToAll"
              type="radio"
              :value="true"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm font-medium text-gray-700">Всем ({{ users.length }} чел.)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="sendToAll"
              type="radio"
              :value="false"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm font-medium text-gray-700">Выбранным</span>
          </label>
        </div>

        <div v-if="!sendToAll && users.length > 0" class="border border-gray-200 rounded-lg p-4 max-h-60 overflow-y-auto">
          <div class="space-y-2">
            <label
              v-for="u in users"
              :key="u.id"
              class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
            >
              <input
                v-model="form.recipient_ids"
                type="checkbox"
                :value="u.id"
                class="w-4 h-4 rounded border-gray-300 text-blue-600"
              />
              <span class="text-sm text-gray-900">
                {{ [u.first_name, u.last_name].filter(Boolean).join(' ') || u.username || u.telegram_chat_id }}
                <span v-if="u.username" class="text-gray-500">@{{ u.username }}</span>
              </span>
            </label>
          </div>
        </div>

        <p v-if="users.length === 0" class="text-sm text-gray-500">Нет пользователей бота. Они появятся после /start.</p>
      </div>

      <div v-if="submitError" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">{{ submitError }}</p>
      </div>

      <div v-if="submitSuccess" class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-green-800">Рассылка выполнена: отправлено {{ submitResult?.sent ?? 0 }} из {{ submitResult?.total ?? 0 }}</p>
        <p v-if="submitResult?.failed?.length" class="text-amber-700 text-sm mt-1">Ошибки: {{ submitResult.failed.length }}</p>
      </div>

      <div class="flex gap-3">
        <button
          type="submit"
          :disabled="sending || !canSubmit"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
        >
          <div v-if="sending" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          Отправить рассылку
        </button>
        <router-link
          to="/admin/shop-bot-users"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          Отмена
        </router-link>
      </div>
    </form>

    <MediaSelector
      :open="showMediaSelector"
      :multiple="false"
      :allowedTypes="form.type === 'photo' ? ['photo'] : ['video']"
      :currentSelection="selectedMedia ? [selectedMedia] : []"
      @close="showMediaSelector = false"
      @select="onMediaSelect"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useShopStore } from '@/stores/shop';
import apiClient from '@/api/axios';
import MediaSelector from '@/components/admin/MediaSelector.vue';

const shopStore = useShopStore();
const users = ref([]);
const loading = ref(false);
const form = ref({
  type: 'text',
  text: '',
  media_id: null,
  recipient_ids: [],
});
const sendToAll = ref(true);
const sending = ref(false);
const submitError = ref(null);
const submitSuccess = ref(false);
const submitResult = ref(null);
const showMediaSelector = ref(false);
const selectedMediaItem = ref(null);

const selectedMedia = computed(() => {
  if (form.value.media_id && selectedMediaItem.value) return selectedMediaItem.value;
  return null;
});

function clearMedia() {
  form.value.media_id = null;
  selectedMediaItem.value = null;
}

function onMediaSelect(payload) {
  const items = Array.isArray(payload) ? payload : (payload ? [payload] : []);
  if (items.length > 0) {
    const item = items[0];
    form.value.media_id = item.id;
    selectedMediaItem.value = { id: item.id, url: item.url || item.path, name: item.name };
  }
  showMediaSelector.value = false;
}

const canSubmit = computed(() => {
  if (form.value.type === 'text') return (form.value.text || '').trim().length > 0;
  if (form.value.type === 'photo' || form.value.type === 'video') {
    return form.value.media_id != null;
  }
  return false;
});

async function fetchUsers() {
  if (!shopStore.selectedShopId) return;
  loading.value = true;
  try {
    const res = await apiClient.get(`/admin/shops/${shopStore.selectedShopId}/bot-users`);
    users.value = res.data || [];
  } catch (e) {
    users.value = [];
  } finally {
    loading.value = false;
  }
}

async function submit() {
  if (!shopStore.selectedShopId) return;
  sending.value = true;
  submitError.value = null;
  submitSuccess.value = false;
  submitResult.value = null;
  try {
    const payload = {
      type: form.value.type,
      text: (form.value.text || '').trim() || undefined,
      media_id: form.value.media_id || undefined,
      recipient_ids: sendToAll.value ? undefined : form.value.recipient_ids,
    };
    const res = await apiClient.post(`/admin/shops/${shopStore.selectedShopId}/bot-users/broadcast`, payload);
    submitSuccess.value = true;
    submitResult.value = res.data;
  } catch (e) {
    submitError.value = e.response?.data?.message || 'Ошибка отправки рассылки';
  } finally {
    sending.value = false;
  }
}

watch(() => shopStore.selectedShopId, fetchUsers);
onMounted(fetchUsers);
</script>
