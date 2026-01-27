<template>
  <div class="space-y-6 bg-transparent">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button
          v-if="selectedFolder"
          @click="handleBack"
          class="h-10 w-10 flex items-center justify-center rounded-lg hover:bg-accent/10 hover:text-accent transition-colors"
        >
          ←
        </button>
        <div>
          <h1 class="text-3xl font-semibold text-foreground">
            {{ selectedFolder ? `Медиа менеджер - ${selectedFolder.name}` : 'Медиа менеджер - Список папок' }}
          </h1>
          <p class="text-muted-foreground mt-1">
            {{ selectedFolder ? 'Загрузка файлов' : 'Управление медиа файлами' }}
          </p>
        </div>
      </div>
      <div class="flex gap-2">
        <div v-if="!selectedFolder && !selectionMode">
          <button
            @click="handleToggleCreateFolder"
            :disabled="loading"
            class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span>+</span>
            <span>{{ loading ? 'Создание...' : 'Создать папку' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && folders.length === 0" class="flex items-center justify-center py-12">
      <p class="text-muted-foreground">Загрузка папок...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
      <p class="text-destructive">{{ error }}</p>
    </div>

    <!-- Search -->
    <div v-if="!selectedFolder && !loading" class="relative">
      <span class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">🔍</span>
      <input
        type="text"
        placeholder="Поиск папок..."
        v-model="searchQuery"
        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pl-9 text-sm"
      />
    </div>

    <!-- Folders Grid -->
    <div v-if="!selectedFolder && !loading" class="grid gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      <div
        v-for="folder in filteredFolders"
        :key="folder.id"
        class="group relative"
      >
        <div
          class="cursor-pointer"
          @click="handleFolderClick(folder)"
        >
          <div class="relative aspect-square mb-2 bg-transparent rounded-lg overflow-hidden flex items-center justify-center">
            <img 
              :src="getFolderIcon(folder)" 
              :alt="folder.name"
              class="w-full h-full object-contain max-w-[66.67%] max-h-[66.67%]"
              @error="handleFolderImageError"
            />
            <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded z-10">
              {{ folder.count || 0 }}
            </div>
          </div>
          <p class="text-sm font-medium text-center text-foreground truncate">{{ folder.name }}</p>
          <p class="text-xs text-muted-foreground text-center">{{ folder.count || 0 }} файлов</p>
        </div>
        <button
          v-if="!folder.protected && !selectionMode"
          @click.stop="handleDeleteFolder(folder)"
          class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center bg-destructive text-white rounded text-xs hover:bg-destructive/90"
          title="Удалить папку"
        >
          ✕
        </button>
        <div
          v-if="folder.protected"
          class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center bg-accent/20 text-accent rounded text-xs"
          title="Защищенная папка"
        >
          🔒
        </div>
      </div>
    </div>

    <!-- Upload Interface -->
    <div v-if="selectedFolder" class="rounded-xl shadow-sm p-6 bg-card border border-border">
      <div class="w-full">
        <!-- Хлебные крошки -->
        <div class="mb-4 flex items-center gap-2 text-sm flex-wrap">
          <button
            @click="handleBack"
            class="px-2 py-1 rounded-md text-sm font-medium hover:bg-accent/10 text-muted-foreground hover:text-foreground transition-colors"
            title="В корневую папку"
          >
            🏠
          </button>
          <span v-if="breadcrumbs.length > 0" class="text-muted-foreground">/</span>
          <button
            v-for="(crumb, index) in breadcrumbs"
            :key="crumb.id || 'root'"
            @click="handleBreadcrumbClick(crumb)"
            :class="[
              'px-2 py-1 rounded-md text-sm transition-colors',
              index === breadcrumbs.length - 1
                ? 'font-semibold text-foreground cursor-default'
                : 'text-muted-foreground hover:text-foreground hover:bg-accent/10 cursor-pointer'
            ]"
          >
            {{ crumb.name }}
          </button>
        </div>
        
        <div class="flex gap-2 mb-6 items-center justify-between">
          <button
            @click="handleBack"
            class="px-4 py-2 rounded-md text-sm font-medium hover:bg-accent/10"
          >
            назад
          </button>
          <!-- Кнопка создания папки внутри текущей папки -->
          <button
            v-if="!isTrashFolder && !selectionMode"
            @click="handleCreateFolder"
            :disabled="loading"
            class="px-4 py-2 rounded-md text-sm font-medium bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
          >
            <span>📁</span>
            <span>{{ loading ? 'Создание...' : 'Создать папку' }}</span>
          </button>
        </div>

        <!-- Upload Tab Content -->
        <div v-if="!isTrashFolder" class="space-y-6 pt-6">
          <div
            class="border-2 border-dashed border-border rounded-lg p-8 transition-colors"
            :class="{ 'border-accent bg-accent/5': isDragging }"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
          >
            <p class="text-center text-muted-foreground mb-4">
              {{ isDragging ? 'Отпустите файлы для загрузки' : 'Перетащите файлы сюда или нажмите кнопку "Файлы". Поддерживаются все типы файлов до 10 МБ' }}
            </p>

            <div class="flex gap-2 max-w-3xl mx-auto">
              <label class="flex-1">
                <input
                  ref="fileInput"
                  type="file"
                  multiple
                  class="hidden"
                  @change="handleFileSelect"
                />
                <button
                  type="button"
                  @click="handleFileButtonClick"
                  @drop.prevent="handleDropOnButton"
                  @dragover.prevent="isDraggingButton = true"
                  @dragleave.prevent="isDraggingButton = false"
                  class="w-full h-11 px-4 border border-border bg-background/50 hover:bg-accent/10 hover:text-accent hover:border-accent rounded-lg inline-flex items-center justify-center transition-colors"
                  :class="{ 'border-accent bg-accent/10': isDraggingButton }"
                >
                  {{ isDraggingButton ? 'Отпустите файлы' : '+ Файлы' }}
                </button>
              </label>

              <button
                @click="handleUpload"
                :disabled="!canUpload"
                class="flex-1 h-11 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center"
              >
                {{ uploading ? 'Загрузка...' : '⬆ Загрузить' }}
              </button>

              <button
                @click="handleCancel"
                class="flex-1 h-11 px-4 border border-border bg-background/50 hover:bg-accent/10 hover:text-accent hover:border-accent rounded-lg inline-flex items-center justify-center"
              >
                ✕ Отменить
              </button>
            </div>

            <div v-if="selectedFiles.length > 0" class="mt-6">
              <p class="text-sm text-muted-foreground mb-4">
                Выбрано файлов: {{ selectedFiles.length }}
                <span v-if="uploadProgress.total > 0" class="ml-2">
                  ({{ uploadProgress.completed }}/{{ uploadProgress.total }} загружено)
                </span>
              </p>
              <!-- Общий прогресс загрузки -->
              <div v-if="uploading && uploadProgress.total > 0" class="mb-4">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs text-muted-foreground">Общий прогресс</span>
                  <span class="text-xs text-muted-foreground">{{ Math.round((uploadProgress.completed / uploadProgress.total) * 100) }}%</span>
                </div>
                <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                  <div
                    class="h-full bg-accent transition-all duration-300"
                    :style="{ width: `${(uploadProgress.completed / uploadProgress.total) * 100}%` }"
                  ></div>
                </div>
              </div>
              <div class="grid gap-3 md:grid-cols-4 lg:grid-cols-6 max-h-96 overflow-y-auto">
                <div
                  v-for="(file, index) in selectedFiles"
                  :key="index"
                  class="group relative aspect-square rounded-lg overflow-hidden border border-border bg-muted/30"
                >
                  <!-- Превью для фото -->
                  <img
                    v-if="isImageFile(file)"
                    :src="getFilePreview(file)"
                    :alt="file.name"
                    class="w-full h-full object-cover"
                  />
                  <!-- Превью для видео -->
                  <video
                    v-else-if="isVideoFile(file)"
                    :src="getFilePreview(file)"
                    class="w-full h-full object-cover"
                    muted
                    @error="(e) => { e.target.style.display = 'none'; }"
                  />
                  <!-- Иконки для других типов файлов -->
                  <div
                    v-else
                    class="w-full h-full flex flex-col items-center justify-center bg-muted/50 p-4"
                  >
                    <div class="text-5xl mb-2">
                      {{ getFileIconFromFile(file) }}
                    </div>
                    <p class="text-xs text-muted-foreground text-center truncate w-full px-2">
                      {{ getFileExtension(file.name)?.toUpperCase() || 'FILE' }}
                    </p>
                  </div>
                  <!-- Overlay с информацией -->
                  <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1 p-2">
                    <p class="text-white text-xs text-center truncate w-full px-2" :title="file.name">
                      {{ file.name }}
                    </p>
                    <p class="text-white/80 text-xs">
                      {{ formatFileSize(file.size) }}
                    </p>
                  </div>
                  <!-- Прогресс загрузки для файла -->
                  <div v-if="file.uploadProgress !== undefined" class="absolute bottom-0 left-0 right-0 bg-black/80 p-1">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-xs text-white truncate flex-1 mr-2">{{ file.name }}</span>
                      <span 
                        v-if="!file.uploadError"
                        class="text-xs text-white/80 whitespace-nowrap"
                      >
                        {{ file.uploadProgress }}%
                      </span>
                      <span 
                        v-else
                        class="text-xs text-red-400 whitespace-nowrap"
                        title="Ошибка загрузки"
                      >
                        ✕ Ошибка
                      </span>
                    </div>
                    <div 
                      v-if="!file.uploadError"
                      class="w-full h-1 bg-muted/50 rounded-full overflow-hidden"
                    >
                      <div
                        class="h-full transition-all duration-300"
                        :class="file.uploadProgress === 100 ? 'bg-green-500' : 'bg-accent'"
                        :style="{ width: `${file.uploadProgress}%` }"
                      ></div>
                    </div>
                    <div 
                      v-else
                      class="w-full h-1 bg-red-500/50 rounded-full"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading Media -->
        <div v-if="loadingMedia" class="flex items-center justify-center py-8">
          <p class="text-muted-foreground">Загрузка файлов...</p>
        </div>

        <!-- Nested Folders Grid -->
        <div v-if="nestedFolders.length > 0 && !loadingMedia" class="pt-6">
          <h3 class="text-lg font-semibold mb-4">Папки ({{ nestedFolders.length }})</h3>
          <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <div
              v-for="folder in nestedFolders"
              :key="folder.id"
              class="group relative"
            >
              <div
                class="cursor-pointer"
                @click="handleFolderClick(folder)"
              >
                <div class="relative aspect-square mb-2 bg-transparent rounded-lg overflow-hidden flex items-center justify-center hover:bg-accent/5 transition-colors">
                  <img 
                    :src="getFolderIcon(folder)" 
                    :alt="folder.name"
                    class="w-full h-full object-contain max-w-[66.67%] max-h-[66.67%]"
                    @error="handleFolderImageError"
                  />
                </div>
                <div class="text-center">
                  <p class="text-sm font-medium text-foreground truncate" :title="folder.name">
                    {{ folder.name }}
                  </p>
                  <div v-if="folder.protected" class="flex items-center justify-center gap-1 mt-1">
                    <span class="text-xs" title="Защищенная папка">🔒</span>
                  </div>
                </div>
              </div>
              <!-- Кнопки для обычных папок -->
              <template v-if="!isTrashFolder">
                <button
                  v-if="!folder.protected && !selectionMode"
                  @click.stop="handleDeleteFolder(folder)"
                  class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center bg-destructive text-white rounded text-xs hover:bg-destructive/90 z-10"
                  title="Удалить папку"
                >
                  ✕
                </button>
                <div
                  v-if="folder.protected"
                  class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center bg-accent/20 text-accent rounded text-xs z-10"
                  title="Защищенная папка"
                >
                  🔒
                </div>
              </template>
              
              <!-- Кнопки для корзины -->
              <template v-else>
                <button
                  @click.stop="handleRestoreFolder(folder)"
                  class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center bg-green-500 text-white rounded text-xs hover:bg-green-600 z-10"
                  title="Восстановить папку"
                >
                  ↩️
                </button>
                <button
                  v-if="!selectionMode"
                  @click.stop="handleDeleteFolder(folder)"
                  class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center bg-destructive text-white rounded text-xs hover:bg-destructive/90 z-10"
                  title="Удалить навсегда"
                >
                  🗑
                </button>
              </template>
            </div>
          </div>
        </div>

        <!-- Фильтры и поиск -->
        <div v-if="selectedFolder && !isTrashFolder" class="pt-6 space-y-4">
          <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
            <!-- Поиск -->
            <div class="flex-1 w-full sm:w-auto">
              <div class="relative">
                <span class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">🔍</span>
                <input
                  type="text"
                  v-model="fileSearchQuery"
                  @keyup.enter="handleFileSearch(true)"
                  @input="handleFileSearch(false)"
                  placeholder="Поиск файлов..."
                  class="flex h-10 w-full sm:w-64 rounded-md border border-input bg-background px-3 py-2 pl-9 text-sm"
                />
              </div>
            </div>
            
            <!-- Фильтр по типу -->
            <select
              v-model="fileTypeFilter"
              @change="handleTypeFilter"
              class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="">Все типы</option>
              <option value="photo">Фото</option>
              <option value="video">Видео</option>
              <option value="document">Документы</option>
              <option value="audio">Аудио</option>
            </select>
            
            <!-- Сортировка -->
            <select
              @change="(e) => {
                const selected = sortOptions.find(opt => opt.label === e.target.value)
                if (selected) {
                  handleSortChange(selected.value, selected.order)
                }
              }"
              :value="currentSortLabel"
              class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm min-w-[180px]"
            >
              <option v-for="option in sortOptions" :key="`${option.value}-${option.order}`" :value="option.label">
                {{ option.label }}
              </option>
            </select>
            
            <!-- Количество на странице -->
            <div class="flex items-center gap-2">
              <label class="text-sm text-muted-foreground whitespace-nowrap">На странице:</label>
              <select
                v-model.number="perPage"
                @change="handlePerPageChange"
                class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option v-for="option in perPageOptions" :key="option" :value="option">
                  {{ option }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Uploaded Files Grid -->
        <div v-if="mediaFiles.length > 0 && !loadingMedia" class="pt-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">
              Загруженные файлы 
              <span v-if="paginationData">
                ({{ paginationData.from }}-{{ paginationData.to }} из {{ paginationData.total }})
              </span>
              <span v-else>
                ({{ mediaFiles.length }})
              </span>
              <span v-if="isTrashFolder" class="text-sm text-muted-foreground ml-2">(Корзина)</span>
            </h3>
            <!-- Кнопка очистки корзины -->
            <button
              v-if="isTrashFolder && mediaFiles.length > 0"
              @click="handleClearTrash"
              :disabled="clearingTrash"
              class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
            >
              {{ clearingTrash ? 'Удаление...' : '🗑 Очистить все' }}
            </button>
          </div>
          <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <div
              v-for="file in mediaFiles"
              :key="file.id"
              :class="[
                'group bg-background border rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow flex flex-col',
                isSelectionModeActive && (file.type === 'photo' || file.type === 'video') 
                  ? (isFileSelected(file) ? 'border-primary border-2 cursor-pointer' : 'border-border cursor-pointer hover:border-primary')
                  : 'border-border'
              ]"
              @click="isSelectionModeActive && (file.type === 'photo' || file.type === 'video') ? openFilePreview(file) : null"
            >
              <!-- Превью изображения -->
              <div class="relative aspect-video bg-muted/30 overflow-hidden flex-shrink-0">
                <!-- Превью для фото -->
                <img
                  v-if="file.type === 'photo'"
                  :src="file.url"
                  :alt="file.original_name"
                  class="w-full h-full object-cover"
                />
                <!-- Превью для видео -->
                <video
                  v-else-if="file.type === 'video'"
                  :src="file.url"
                  class="w-full h-full object-cover"
                  muted
                  @mouseenter="(e) => { try { e.target.play() } catch(err) {} }"
                  @mouseleave="(e) => { try { e.target.pause(); e.target.currentTime = 0; } catch(err) {} }"
                  @error="(e) => { e.target.style.display = 'none'; }"
                />
                <!-- Иконки для других типов файлов -->
                <div
                  v-else
                  class="w-full h-full flex flex-col items-center justify-center bg-muted/50 p-4"
                >
                  <div class="text-5xl mb-2">
                    {{ getFileIcon(file) || '📎' }}
                  </div>
                  <p class="text-xs text-muted-foreground text-center truncate w-full px-2">
                    {{ file.extension?.toUpperCase() || 'FILE' }}
                  </p>
                </div>
                <!-- Индикатор выбранного файла -->
                <div
                  v-if="isSelectionModeActive && isFileSelected(file)"
                  class="absolute top-2 left-2 w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs font-bold z-10"
                >
                  ✓
                </div>
                <!-- Кнопка выбора (вместо просмотра в режиме выбора) -->
                <button
                  v-if="isSelectionModeActive && (file.type === 'photo' || file.type === 'video')"
                  @click.stop="openFilePreview(file)"
                  :class="[
                    'absolute inset-0 flex items-center justify-center transition-opacity cursor-pointer',
                    isFileSelected(file) ? 'opacity-100 bg-primary/30' : 'opacity-0 group-hover:opacity-100 bg-primary/20 hover:bg-primary/30'
                  ]"
                  :title="isFileSelected(file) ? 'Выбрано' : 'Выбрать'"
                >
                  <div class="w-16 h-16 rounded-full backdrop-blur-sm flex items-center justify-center shadow-lg hover:scale-110 transition-transform bg-primary/95 text-white">
                    <span class="text-2xl">✓</span>
                  </div>
                </button>
                <!-- Кнопка просмотра (только если не режим выбора) -->
                <button
                  v-else-if="!isSelectionModeActive && isPreviewable(file)"
                  @click.stop="openFilePreview(file)"
                  class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/20 hover:bg-black/30 cursor-pointer"
                  title="Просмотр"
                >
                  <div class="w-16 h-16 rounded-full backdrop-blur-sm flex items-center justify-center shadow-lg hover:scale-110 transition-transform bg-white/95">
                    <span class="text-2xl">👁️</span>
                  </div>
                </button>
              </div>

              <!-- Информация о файле -->
              <div class="p-3 space-y-1 flex-grow">
                <p class="font-semibold text-sm text-foreground truncate" :title="file.original_name">
                  {{ file.original_name }}
                </p>
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                  <span>{{ formatFileSize(file.size) }}</span>
                  <span v-if="file.extension" class="text-blue-600 font-medium">
                    .{{ file.extension.toUpperCase() }}
                  </span>
                </div>
                <p v-if="file.width && file.height" class="text-xs text-muted-foreground">
                  {{ file.width }} × {{ file.height }}
                </p>
              </div>

              <!-- Кнопки действий (всегда внизу) -->
              <div v-if="!selectionMode" class="px-3 pb-3 flex gap-2 mt-auto">
                <!-- Кнопки для обычных папок -->
                <template v-if="!isTrashFolder">
                  <!-- Скачать -->
                  <button
                    @click.stop="handleDownloadFile(file)"
                    class="flex-1 h-9 flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                    title="Скачать"
                  >
                    <span class="text-sm">⬇</span>
                  </button>
                  <!-- Редактировать (только для фото) -->
                  <button
                    v-if="file.type === 'photo'"
                    @click.stop="handleEditFile(file)"
                    class="flex-1 h-9 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors"
                    title="Редактировать"
                  >
                    <span class="text-sm">✏️</span>
                  </button>
                  <!-- Переместить -->
                  <button
                    @click.stop="handleMoveFile(file)"
                    class="flex-1 h-9 flex items-center justify-center bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors"
                    title="Переместить"
                  >
                    <span class="text-sm">📁</span>
                  </button>
                  <!-- Удалить -->
                  <button
                    @click.stop="handleDeleteFile(file)"
                    class="flex-1 h-9 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                    title="Удалить"
                  >
                    <span class="text-sm">🗑</span>
                  </button>
                </template>
                
                <!-- Кнопки для корзины -->
                <template v-else>
                  <!-- Восстановить -->
                  <button
                    @click.stop="handleRestoreFile(file)"
                    class="flex-1 h-9 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors"
                    title="Восстановить"
                  >
                    <span class="text-sm">↩️</span>
                  </button>
                  <!-- Удалить совсем -->
                  <button
                    @click.stop="handleDeleteFile(file)"
                    class="flex-1 h-9 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                    title="Удалить совсем"
                  >
                    <span class="text-sm">🗑</span>
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Пагинация -->
        <div v-if="paginationData && paginationData.total > 0 && !loadingMedia" class="flex flex-col sm:flex-row items-center justify-between pt-6 border-t border-border gap-4">
          <div class="text-sm text-muted-foreground">
            <span v-if="paginationData.last_page > 1">
              Страница {{ paginationData.current_page }} из {{ paginationData.last_page }}
            </span>
            <span v-else>
              Всего файлов: {{ paginationData.total }}
            </span>
          </div>
          <div v-if="paginationData.last_page > 1" class="flex gap-2 items-center">
            <button
              @click="handlePageChange(paginationData.current_page - 1)"
              :disabled="paginationData.current_page === 1"
              class="px-3 py-2 rounded-md border border-border bg-background hover:bg-accent/10 disabled:opacity-50 disabled:cursor-not-allowed text-sm transition-colors"
            >
              ← Назад
            </button>
            
            <!-- Номера страниц -->
            <div class="flex gap-1">
              <button
                v-for="pageNum in getPageNumbers(paginationData.current_page, paginationData.last_page)"
                :key="pageNum"
                @click="handlePageChange(pageNum)"
                :class="[
                  'px-3 py-2 rounded-md border text-sm transition-colors min-w-[40px]',
                  pageNum === paginationData.current_page
                    ? 'bg-accent text-accent-foreground border-accent font-semibold'
                    : 'border-border bg-background hover:bg-accent/10'
                ]"
              >
                {{ pageNum }}
              </button>
            </div>
            
            <button
              @click="handlePageChange(paginationData.current_page + 1)"
              :disabled="paginationData.current_page === paginationData.last_page"
              class="px-3 py-2 rounded-md border border-border bg-background hover:bg-accent/10 disabled:opacity-50 disabled:cursor-not-allowed text-sm transition-colors"
            >
              Вперед →
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="nestedFolders.length === 0 && mediaFiles.length === 0 && !loadingMedia" class="text-center py-12">
          <p class="text-muted-foreground">
            {{ isTrashFolder ? 'Корзина пуста' : fileSearchQuery || fileTypeFilter ? 'Файлы не найдены' : 'В этой папке пока нет файлов и папок' }}
          </p>
          <!-- Debug info -->
          <p v-if="selectedFolder" class="text-xs text-muted-foreground mt-2">
            Debug: folder_id={{ selectedFolder.id }}, is_trash={{ selectedFolder.is_trash }}, 
            nestedFolders={{ nestedFolders.length }}, mediaFiles={{ mediaFiles.length }}, loadingMedia={{ loadingMedia }}
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- FsLightbox для просмотра фото и видео -->
  <FsLightbox
    v-if="lightboxSources.length > 0"
    :toggler="lightboxToggler"
    :sources="lightboxSources"
    :slide="lightboxSlide"
  />

  <!-- Image Editor -->
  <ImageEditor
    :show="showImageEditor"
    :file="selectedFileForEdit"
    @close="showImageEditor = false"
    @saved="handleImageSaved"
  />

  <!-- Move File Modal -->
  <div v-if="showMoveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-white border border-border rounded-lg shadow-2xl w-full max-w-md p-6">
      <h3 class="text-lg font-semibold mb-4">Переместить файл</h3>
      <p v-if="selectedFileForMove" class="text-sm text-muted-foreground mb-4">
        {{ selectedFileForMove.original_name }}
      </p>
      
      <div class="mb-4">
        <label class="text-sm font-medium mb-2 block">Выберите папку</label>
        <div class="border border-border rounded-lg max-h-96 overflow-y-auto bg-white">
          <div class="p-2">
            <!-- Корневая папка -->
            <button
              @click="selectMoveFolder(null)"
              :class="[
                'w-full text-left px-3 py-2 rounded hover:bg-accent/10 transition-colors flex items-center gap-2',
                selectedMoveFolderId === null ? 'bg-accent/20 border border-accent/40' : ''
              ]"
            >
              <img 
                :src="getDefaultFolderIcon()" 
                alt="Корневая папка"
                class="w-[13.33px] h-[13.33px] object-contain"
                @error="handleFolderImageError"
              />
              <span class="flex-1">Корневая папка</span>
            </button>
            
            <!-- Список папок -->
            <div v-for="folder in allFolders" :key="folder.id" class="mt-1">
              <button
                @click="selectMoveFolder(folder.id)"
                :class="[
                  'w-full text-left px-3 py-2 rounded hover:bg-accent/10 transition-colors flex items-center gap-2',
                  selectedMoveFolderId === folder.id ? 'bg-accent/20 border border-accent/40' : ''
                ]"
              >
                <img 
                  :src="getFolderIcon(folder)" 
                  :alt="folder.name"
                  class="w-[13.33px] h-[13.33px] object-contain"
                  @error="handleFolderImageError"
                />
                <span class="flex-1">{{ folder.name }}</span>
                <span v-if="folder.count" class="text-xs text-muted-foreground">
                  {{ folder.count }} файлов
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button
          @click="showMoveModal = false"
          class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
        >
          Отмена
        </button>
        <button
          @click="confirmMoveFile"
          :disabled="moving"
          class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
        >
          {{ moving ? 'Перемещение...' : 'Переместить' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
    import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { apiGet, apiPost, apiDelete, apiPut } from '../../utils/api'
import { useAuthToken } from '../../composables/useAuthToken'
import FsLightbox from 'fslightbox-vue'
import ImageEditor from './ImageEditor.vue'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

const API_BASE = '/api/v1'

export default {
  name: 'Media',
  components: {
    FsLightbox,
    ImageEditor
  },
  props: {
    selectionMode: {
      type: Boolean,
      default: false
    },
    countFile: {
      type: Number,
      default: 1
    },
    selectedFiles: {
      type: Array,
      default: () => []
    }
  },
  emits: ['file-selected'],
  setup(props, { emit }) {
    console.log('[Media] setup() вызван')
    
    const loading = ref(false)
    const loadingMedia = ref(false)
    const uploading = ref(false)
    const error = ref(null)
    const searchQuery = ref('')
    const folders = ref([])
    const selectedFolder = ref(null)
    const newFolderName = ref('')
    const isCreateFolderOpen = ref(false)
    const selectedFiles = ref([])
    const mediaFiles = ref([])
    const isDragging = ref(false)
    const isDraggingButton = ref(false)
    const fileInput = ref(null)
    const uploadProgress = ref({
      total: 0,
      completed: 0
    })
    const lightboxToggler = ref(false)
    const lightboxSources = ref([])
    const lightboxSlide = ref(1)
    const showImageEditor = ref(false)
    const selectedFileForEdit = ref(null)
    const showMoveModal = ref(false)
    const selectedFileForMove = ref(null)
    const selectedMoveFolderId = ref(null)
    const allFolders = ref([])
    const moving = ref(false)
    const clearingTrash = ref(false)
    const nestedFolders = ref([]) // Вложенные папки в текущей папке
    const breadcrumbs = ref([]) // Хлебные крошки для навигации
    const STORAGE_KEY = 'media_selected_folder_id' // Ключ для localStorage
    
    // Пагинация и фильтрация
    const currentPage = ref(1)
    const perPage = ref(20)
    const perPageOptions = [10, 20, 30, 40, 50, 100]
    const totalFiles = ref(0)
    const lastPage = ref(1)
    const fileSearchQuery = ref('')
    const fileTypeFilter = ref('')
    const fileSortBy = ref('created_at')
    const fileSortOrder = ref('desc')
    const paginationData = ref(null)
    const searchTimeout = ref(null)
    
    // Проверяем режим выбора из URL параметров
    const isUrlSelectionMode = computed(() => {
      const urlParams = new URLSearchParams(window.location.search)
      return urlParams.get('select') === 'true'
    })
    
    // Объединенный режим выбора (из props или URL)
    const isSelectionModeActive = computed(() => {
      return props.selectionMode || isUrlSelectionMode.value
    })
    
    // Опции сортировки
    const sortOptions = [
      { value: 'created_at', order: 'desc', label: 'Новые' },
      { value: 'created_at', order: 'asc', label: 'Старые' },
      { value: 'created_at', order: 'asc', label: 'По дате (возр.)' },
      { value: 'created_at', order: 'desc', label: 'По дате (убыв.)' },
      { value: 'original_name', order: 'asc', label: 'По имени (А-Я)' },
      { value: 'original_name', order: 'desc', label: 'По имени (Я-А)' },
      { value: 'size', order: 'asc', label: 'По размеру (возр.)' },
      { value: 'size', order: 'desc', label: 'По размеру (убыв.)' }
    ]
    
    // Computed для текущей выбранной сортировки
    const currentSortLabel = computed(() => {
      const option = sortOptions.find(opt => 
        opt.value === fileSortBy.value && opt.order === fileSortOrder.value
      )
      return option ? option.label : 'Новые'
    })

    // Фильтрация папок по поисковому запросу
    const filteredFolders = computed(() => {
      if (!searchQuery.value) {
        return folders.value
      }
      return folders.value.filter(folder =>
        folder.name.toLowerCase().includes(searchQuery.value.toLowerCase())
      )
    })

    // Проверка, является ли текущая папка корзиной
    const isTrashFolder = computed(() => {
      return selectedFolder.value?.is_trash === true || selectedFolder.value?.id === 4
    })

    // Проверка возможности загрузки
    const canUpload = computed(() => {
      const hasFiles = selectedFiles.value.length > 0
      const hasFolder = !!selectedFolder.value
      const notUploading = !uploading.value
      const result = hasFiles && hasFolder && notUploading
      console.log('[Media] canUpload check:', { hasFiles, hasFolder, notUploading, result, filesCount: selectedFiles.value.length })
      return result
    })

    // Загрузка папок из API
    const fetchFolders = async () => {
      loading.value = true
      error.value = null
      
      try {
        // Не передаем parent_id, контроллер по умолчанию вернет корневые папки
        const response = await apiGet('/folders')
        
        if (!response.ok) {
          let errorMessage = `HTTP error! status: ${response.status}`
          try {
            const errorData = await response.json()
            errorMessage = errorData.message || errorData.error || errorMessage
            console.error('[Media] API Error Data:', errorData)
          } catch (e) {
            const errorText = await response.text()
            console.error('[Media] API Error Response (text):', errorText)
          }
          throw new Error(errorMessage)
        }
        
        const data = await response.json()
        console.log('[Media] API Response:', data)
        
        // Обрабатываем разные форматы ответа
        if (Array.isArray(data)) {
          folders.value = data
        } else if (data.data && Array.isArray(data.data)) {
          folders.value = data.data
        } else {
          folders.value = []
        }
        
        console.log('[Media] Folders loaded:', folders.value.length)
      } catch (err) {
        console.error('[Media] Error fetching folders:', err)
        error.value = 'Ошибка загрузки папок: ' + (err.message || 'Неизвестная ошибка')
      } finally {
        loading.value = false
      }
    }

    // Загрузка файлов из папки с пагинацией и фильтрацией
    const fetchMediaFiles = async (folderId, page = 1, originalFolderId = null) => {
      loadingMedia.value = true
      error.value = null
      
      try {
        // Формируем параметры запроса
        const params = new URLSearchParams()
        params.append('folder_id', folderId)
        params.append('page', page)
        params.append('per_page', perPage.value)
        
        // Если указан originalFolderId, фильтруем файлы по оригинальной папке (для удаленных папок)
        if (originalFolderId) {
          params.append('original_folder_id', originalFolderId)
        }
        
        if (fileSearchQuery.value.trim()) {
          params.append('search', fileSearchQuery.value.trim())
        }
        
        if (fileTypeFilter.value) {
          params.append('type', fileTypeFilter.value)
        }
        
        // Параметры сортировки
        params.append('sort_by', fileSortBy.value)
        params.append('sort_order', fileSortOrder.value)
        
        const response = await apiGet(`/media?${params.toString()}`)
        
        if (!response.ok) {
          let errorMessage = `HTTP error! status: ${response.status}`
          try {
            const errorData = await response.json()
            errorMessage = errorData.message || errorData.error || errorMessage
            console.error('[Media] API Error Data:', errorData)
          } catch (e) {
            const errorText = await response.text()
            console.error('[Media] API Error Response (text):', errorText)
          }
          throw new Error(errorMessage)
        }
        
        const data = await response.json()
        console.log('[Media] Media API Response:', data)
        console.log('[Media] Response structure:', {
          hasData: !!data.data,
          isArray: Array.isArray(data.data),
          hasMeta: !!data.meta,
          hasLinks: !!data.links,
          keys: Object.keys(data)
        })
        
        // Обрабатываем ответ с пагинацией
        let files = []
        
        // Laravel Resource Collection с пагинацией возвращает:
        // { data: [...], meta: { current_page, last_page, per_page, total, from, to }, links: {...} }
        if (data.data && Array.isArray(data.data)) {
          files = data.data
          
          // Проверяем наличие метаданных пагинации
          if (data.meta) {
            paginationData.value = {
              current_page: data.meta.current_page || page,
              last_page: data.meta.last_page || 1,
              per_page: data.meta.per_page || perPage.value,
              total: data.meta.total || 0,
              from: data.meta.from || 0,
              to: data.meta.to || 0
            }
            currentPage.value = data.meta.current_page || page
            lastPage.value = data.meta.last_page || 1
            totalFiles.value = data.meta.total || 0
          } else if (data.current_page !== undefined) {
            // Альтернативный формат: данные пагинации в корне объекта
            paginationData.value = {
              current_page: data.current_page || page,
              last_page: data.last_page || 1,
              per_page: data.per_page || perPage.value,
              total: data.total || 0,
              from: data.from || 0,
              to: data.to || 0
            }
            currentPage.value = data.current_page || page
            lastPage.value = data.last_page || 1
            totalFiles.value = data.total || 0
          } else {
            // Нет пагинации
            paginationData.value = null
          }
        } else if (Array.isArray(data)) {
          files = data
          paginationData.value = null
        } else {
          files = []
          paginationData.value = null
        }
        
        console.log('[Media] Parsed pagination:', paginationData.value)
        
        console.log('[Media] Parsed files:', files.length, 'Total:', totalFiles.value, 'Page:', currentPage.value, 'of', lastPage.value)
        
        // Принудительно обновляем через nextTick для корректной реактивности
        await nextTick()
        mediaFiles.value = [...files]
        
        await nextTick()
      } catch (err) {
        console.error('[Media] Error fetching media files:', err)
        error.value = 'Ошибка загрузки файлов: ' + (err.message || 'Неизвестная ошибка')
      } finally {
        loadingMedia.value = false
      }
    }
    
    // Обработка изменения страницы
    const handlePageChange = (page) => {
      if (selectedFolder.value) {
        fetchMediaFiles(selectedFolder.value.id, page)
      }
    }
    
    // Обработка изменения количества файлов на странице
    const handlePerPageChange = () => {
      currentPage.value = 1
      if (selectedFolder.value) {
        fetchMediaFiles(selectedFolder.value.id, 1)
      }
    }
    
    // Обработка поиска файлов с debounce
    const handleFileSearch = (immediate = false) => {
      // Очищаем предыдущий таймер
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
        searchTimeout.value = null
      }
      
      if (immediate) {
        // Немедленный поиск (при нажатии Enter)
        currentPage.value = 1
        if (selectedFolder.value) {
          fetchMediaFiles(selectedFolder.value.id, 1)
        }
      } else {
        // Отложенный поиск с задержкой 500ms
        searchTimeout.value = setTimeout(() => {
          currentPage.value = 1
          if (selectedFolder.value) {
            fetchMediaFiles(selectedFolder.value.id, 1)
          }
          searchTimeout.value = null
        }, 500)
      }
    }
    
    // Обработка фильтрации по типу
    const handleTypeFilter = () => {
      currentPage.value = 1
      if (selectedFolder.value) {
        fetchMediaFiles(selectedFolder.value.id, 1)
      }
    }
    
    // Обработка изменения сортировки
    const handleSortChange = (sortValue, sortOrder) => {
      fileSortBy.value = sortValue
      fileSortOrder.value = sortOrder
      currentPage.value = 1
      if (selectedFolder.value) {
        fetchMediaFiles(selectedFolder.value.id, 1)
      }
    }
    
    // Вычисление номеров страниц для отображения
    const getPageNumbers = (currentPage, lastPage) => {
      const pages = []
      const maxVisible = 5
      
      if (lastPage <= maxVisible) {
        // Если страниц мало, показываем все
        for (let i = 1; i <= lastPage; i++) {
          pages.push(i)
        }
      } else {
        // Показываем умную пагинацию
        if (currentPage <= 3) {
          // В начале: 1, 2, 3, 4, 5
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
        } else if (currentPage >= lastPage - 2) {
          // В конце: ... last-4, last-3, last-2, last-1, last
          for (let i = lastPage - 4; i <= lastPage; i++) {
            pages.push(i)
          }
        } else {
          // В середине: current-2, current-1, current, current+1, current+2
          for (let i = currentPage - 2; i <= currentPage + 2; i++) {
            pages.push(i)
          }
        }
      }
      
      return pages
    }

    // Создание новой папки
    const handleCreateFolder = async () => {
      // Определяем parent_id: если мы внутри папки, создаем в ней, иначе в корне
      const parentId = selectedFolder.value ? selectedFolder.value.id : null
      const folderLocation = selectedFolder.value 
        ? `в папке "${selectedFolder.value.name}"` 
        : 'в корне'

      // Используем SweetAlert2 для ввода названия папки
      const { value: folderName } = await Swal.fire({
        title: 'Создать новую папку',
        html: `Создать папку ${folderLocation}`,
        input: 'text',
        inputPlaceholder: 'Введите название папки',
        inputValidator: (value) => {
          if (!value || !value.trim()) {
            return 'Название папки не может быть пустым'
          }
          if (value.trim().length < 2) {
            return 'Название папки должно содержать минимум 2 символа'
          }
          if (value.trim().length > 100) {
            return 'Название папки не должно превышать 100 символов'
          }
        },
        showCancelButton: true,
        confirmButtonText: 'Создать',
        cancelButtonText: 'Отмена',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        inputAttributes: {
          autocapitalize: 'off',
          autocorrect: 'off'
        }
      })

      if (!folderName || !folderName.trim()) {
        return
      }

      loading.value = true
      error.value = null

      try {
        const response = await apiPost('/folders', {
          name: folderName.trim(),
          parent_id: parentId
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка создания папки')
        }

        const data = await response.json()
        
        // Показываем уведомление об успехе
        Swal.fire({
          title: 'Папка создана',
          html: `Папка <strong>"${folderName.trim()}"</strong> успешно создана ${folderLocation}.`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        })

        // Обновляем список папок
        await fetchFolders()
        
        // Если мы внутри папки, обновляем её содержимое (вложенные папки)
        if (selectedFolder.value) {
          await fetchNestedFolders(selectedFolder.value.id)
        }
        
        console.log('[Media] Folder created successfully')
      } catch (err) {
        console.error('[Media] Error creating folder:', err)
        
        // Показываем ошибку
        Swal.fire({
          title: 'Ошибка',
          text: err.message || 'Ошибка создания папки',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        
        error.value = err.message || 'Ошибка создания папки'
      } finally {
        loading.value = false
      }
    }

    // Загрузка вложенных папок
    const fetchNestedFolders = async (parentId) => {
      try {
        // Если мы в корзине, запрашиваем все удаленные папки (без parent_id)
        let url
        if (isTrashFolder.value) {
          // Для корзины запрашиваем все удаленные папки
          url = '/folders?trash=1'
        } else {
          // Для обычных папок запрашиваем по parent_id
          url = `/folders?parent_id=${parentId}`
        }
        
        const response = await apiGet(url)
        
        if (!response.ok) {
          console.error('[Media] Error fetching nested folders:', response.status)
          nestedFolders.value = []
          return
        }
        
        const data = await response.json()
        
        // Обрабатываем разные форматы ответа
        if (Array.isArray(data)) {
          nestedFolders.value = data
        } else if (data.data && Array.isArray(data.data)) {
          nestedFolders.value = data.data
        } else {
          nestedFolders.value = []
        }
        
        console.log('[Media] Nested folders loaded:', nestedFolders.value.length, isTrashFolder.value ? '(trash)' : '')
      } catch (err) {
        console.error('[Media] Error fetching nested folders:', err)
        nestedFolders.value = []
      }
    }

    // Построение хлебных крошек
    const buildBreadcrumbs = async (folder) => {
      if (!folder) {
        breadcrumbs.value = []
        return
      }
      
      const crumbs = []
      let currentFolder = folder
      
      // Собираем путь от текущей папки до корня
      while (currentFolder) {
        crumbs.unshift({
          id: currentFolder.id,
          name: currentFolder.name,
          folder: currentFolder
        })
        
        // Проверяем, есть ли уже загруженный parent в объекте
        if (currentFolder.parent && currentFolder.parent.id) {
          currentFolder = currentFolder.parent
        } else if (currentFolder.parent_id) {
          // Если parent не загружен, загружаем родительскую папку
          try {
            const response = await apiGet(`/folders/${currentFolder.parent_id}`)
            if (response.ok) {
              const data = await response.json()
              currentFolder = data.data || data
            } else {
              break
            }
          } catch (err) {
            console.error('[Media] Error loading parent folder:', err)
            break
          }
        } else {
          break
        }
      }
      
      // Добавляем корневую папку в начало
      crumbs.unshift({
        id: null,
        name: 'Корневая папка',
        folder: null
      })
      
      breadcrumbs.value = crumbs
    }
    
    // Сохранение текущей папки в localStorage
    const saveFolderToStorage = (folderId) => {
      try {
        if (folderId) {
          localStorage.setItem(STORAGE_KEY, folderId.toString())
        } else {
          localStorage.removeItem(STORAGE_KEY)
        }
      } catch (err) {
        console.error('[Media] Error saving folder to localStorage:', err)
      }
    }
    
    // Загрузка папки из localStorage
    const loadFolderFromStorage = async () => {
      try {
        const savedFolderId = localStorage.getItem(STORAGE_KEY)
        if (savedFolderId) {
          const folderId = parseInt(savedFolderId)
          if (!isNaN(folderId)) {
            // Загружаем папку по ID
            const response = await apiGet(`/folders/${folderId}`)
            if (response.ok) {
              const data = await response.json()
              const folder = data.data || data
              if (folder) {
                await handleFolderClick(folder, false) // false = не сохранять в storage (уже загружено)
                return true
              }
            } else if (response.status === 404) {
              // Папка не найдена (была удалена)
              console.log('[Media] Folder from localStorage not found (deleted), clearing storage')
              localStorage.removeItem(STORAGE_KEY)
              return false
            }
          } else {
            // Некорректный ID, очищаем
            localStorage.removeItem(STORAGE_KEY)
          }
        }
      } catch (err) {
        console.error('[Media] Error loading folder from localStorage:', err)
        // Очищаем localStorage при любой ошибке
        localStorage.removeItem(STORAGE_KEY)
      }
      return false
    }
    
    // Обработка клика по папке
    const handleFolderClick = async (folder, saveToStorage = true) => {
      console.log('[Media] Folder clicked:', folder)
      
      // Если папка удалена (находится в корзине), загружаем её заново через API
      // чтобы получить полную информацию с родительскими папками
      if (folder.deleted_at || (isTrashFolder.value && folder.id !== 4)) {
        try {
          const response = await apiGet(`/folders/${folder.id}`)
          if (response.ok) {
            const data = await response.json()
            folder = data.data || data
          }
        } catch (err) {
          console.error('[Media] Error loading deleted folder:', err)
        }
      }
      
      selectedFolder.value = folder
      currentPage.value = 1
      fileSearchQuery.value = ''
      fileTypeFilter.value = ''
      fileSortBy.value = 'created_at'
      fileSortOrder.value = 'desc'
      
      // Сохраняем в localStorage (только если не в корзине)
      if (saveToStorage && !isTrashFolder.value) {
        saveFolderToStorage(folder.id)
      }
      
      // Строим хлебные крошки
      await buildBreadcrumbs(folder)
      
      // Если это удаленная папка в корзине, запрашиваем файлы и папки с учетом удаления
      if (folder.deleted_at) {
        // Для удаленных папок запрашиваем файлы из корзины, которые принадлежали этой папке
        // и вложенные удаленные папки
        await Promise.all([
          fetchMediaFiles(4, 1, folder.id), // Запрашиваем файлы из корзины с original_folder_id
          fetchNestedFolders(folder.id, true) // Запрашиваем удаленные папки с parent_id = folder.id
        ])
      } else {
        await Promise.all([
          fetchMediaFiles(folder.id, 1),
          fetchNestedFolders(folder.id)
        ])
      }
    }

    // Обработка клика на кнопку выбора файлов
    const handleFileButtonClick = () => {
      if (fileInput.value) {
        fileInput.value.click()
      }
    }

    // Обработка выбора файлов
    const handleFileSelect = (e) => {
      if (e.target.files) {
        const filesArray = Array.from(e.target.files)
        // Фильтруем файлы по размеру (максимум 10 МБ)
        const maxSize = 10 * 1024 * 1024 // 10 МБ
        const validFiles = filesArray.filter(file => {
          if (file.size > maxSize) {
            console.warn(`[Media] Файл ${file.name} превышает 10 МБ и будет пропущен`)
            return false
          }
          return true
        })
        // Сбрасываем прогресс для новых файлов
        validFiles.forEach(file => {
          file.uploadProgress = undefined
          file.uploadError = undefined
        })
        selectedFiles.value = validFiles
        uploading.value = false // Убеждаемся, что uploading сброшен
        uploadProgress.value = { total: 0, completed: 0 } // Сбрасываем общий прогресс
        if (validFiles.length < filesArray.length) {
          error.value = `Некоторые файлы превышают 10 МБ и не были добавлены`
        }
        console.log('[Media] Files selected:', validFiles.length, 'selectedFiles.value.length:', selectedFiles.value.length)
        // Очищаем input для возможности повторного выбора тех же файлов
        if (e.target) {
          e.target.value = ''
        }
      }
    }

    // Обработка drag & drop в область
    const handleDrop = (e) => {
      isDragging.value = false
      isDraggingButton.value = false
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        const filesArray = Array.from(e.dataTransfer.files)
        // Фильтруем файлы по размеру (максимум 10 МБ)
        const validFiles = filesArray.filter(file => {
          const maxSize = 10 * 1024 * 1024 // 10 МБ
          if (file.size > maxSize) {
            console.warn(`[Media] Файл ${file.name} превышает 10 МБ и будет пропущен`)
            return false
          }
          return true
        })
        // Сбрасываем прогресс для новых файлов
        validFiles.forEach(file => {
          file.uploadProgress = undefined
          file.uploadError = undefined
        })
        selectedFiles.value = validFiles
        uploading.value = false // Убеждаемся, что uploading сброшен
        uploadProgress.value = { total: 0, completed: 0 } // Сбрасываем общий прогресс
        if (validFiles.length < filesArray.length) {
          error.value = `Некоторые файлы превышают 10 МБ и не были добавлены`
        }
        console.log('[Media] Files dropped:', validFiles.length, 'selectedFiles.value.length:', selectedFiles.value.length)
      }
    }

    // Обработка drag & drop на кнопку
    const handleDropOnButton = (e) => {
      isDragging.value = false
      isDraggingButton.value = false
      handleDrop(e)
    }

    // Обработка ошибки загрузки изображения
    const handleImageError = (e, folder) => {
      // Если изображение не загрузилось, скрываем его и показываем эмодзи
      e.target.style.display = 'none'
      // Эмодзи уже будет показан через v-else, но если src есть, нужно его скрыть
      if (folder.src) {
        folder.imageError = true
      }
    }

    // Хранилище для URL объектов (для очистки)
    const filePreviewUrls = ref(new Set())

    // Получить иконку для файла по типу
    // Получить дефолтную иконку папки
    const getDefaultFolderIcon = () => {
      // Используем динамический путь, чтобы избежать проблем с Vite импортом
      return `${window.location.origin}/system/folder.png`
    }

    // Получить путь к иконке папки
    const getFolderIcon = (folder) => {
      if (!folder || !folder.src) {
        // Если нет src, возвращаем дефолтную иконку
        return getDefaultFolderIcon()
      }
      // Формируем путь по полю src: /system/{src}.png
      return `${window.location.origin}/system/${folder.src}.png`
    }

    // Обработчик ошибки загрузки изображения папки (fallback на эмодзи)
    const handleFolderImageError = (event) => {
      // Если изображение не загрузилось, заменяем на эмодзи
      const img = event.target
      const parent = img.parentElement
      if (parent && !parent.querySelector('span.folder-fallback')) {
        img.style.display = 'none'
        const emoji = document.createElement('span')
        // Определяем размер эмодзи в зависимости от класса родителя
        // Уменьшаем размер эмодзи в 1.5 раза: text-6xl (60px) -> text-4xl (36px), text-lg (18px) -> text-sm (14px)
        if (parent.classList.contains('aspect-square') || img.classList.contains('w-full')) {
          emoji.className = 'text-4xl folder-fallback'
        } else {
          emoji.className = 'text-sm folder-fallback'
        }
        emoji.textContent = '📁'
        parent.appendChild(emoji)
      }
    }

    const getFileIcon = (file) => {
      const extension = file.extension?.toLowerCase() || ''
      const type = file.type?.toLowerCase() || ''

      // Если тип уже определен как photo или video, не показываем иконку (будет превью)
      if (type === 'photo' || type === 'video') {
        return null
      }

      // PDF
      if (extension === 'pdf' || (type === 'document' && extension === 'pdf')) {
        return '📄'
      }
      // Word документы
      if (['doc', 'docx'].includes(extension)) {
        return '📝'
      }
      // Excel
      if (['xls', 'xlsx'].includes(extension)) {
        return '📊'
      }
      // PowerPoint
      if (['ppt', 'pptx'].includes(extension)) {
        return '📽️'
      }
      // Архивы
      if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
        return '📦'
      }
      // Текстовые файлы
      if (['txt', 'rtf'].includes(extension)) {
        return '📃'
      }
      // Код
      if (['js', 'ts', 'php', 'py', 'java', 'cpp', 'html', 'css', 'scss', 'json', 'xml'].includes(extension)) {
        return '💻'
      }
      // Аудио
      if (['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'].includes(extension)) {
        return '🎵'
      }
      // По умолчанию
      return '📎'
    }

    // Форматирование размера файла
    const formatFileSize = (bytes) => {
      if (!bytes) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    }

    // Проверка, является ли файл изображением
    const isImageFile = (file) => {
      if (file.type) {
        return file.type.startsWith('image/')
      }
      const ext = getFileExtension(file.name)
      return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)
    }

    // Проверка, является ли файл видео
    const isVideoFile = (file) => {
      if (file.type) {
        return file.type.startsWith('video/')
      }
      const ext = getFileExtension(file.name)
      return ['mp4', 'avi', 'mov', 'webm', 'mkv', 'wmv', 'flv'].includes(ext)
    }

    // Получить расширение файла
    const getFileExtension = (fileName) => {
      if (!fileName) return ''
      const parts = fileName.split('.')
      return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : ''
    }

    // Получить превью файла (URL для File объекта)
    const getFilePreview = (file) => {
      if (file instanceof File) {
        const url = URL.createObjectURL(file)
        filePreviewUrls.value.add(url)
        return url
      }
      return file.url || ''
    }

    // Очистка URL объектов при отмене
    const cleanupFileUrls = () => {
      filePreviewUrls.value.forEach(url => {
        URL.revokeObjectURL(url)
      })
      filePreviewUrls.value.clear()
    }

    // Получить иконку для File объекта
    const getFileIconFromFile = (file) => {
      const extension = getFileExtension(file.name)
      return getFileIcon({ extension, type: file.type })
    }

    // Загрузка файла с отслеживанием прогресса
    const uploadFileWithProgress = (file, folderId, onProgress) => {
      return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest()
        const formData = new FormData()
        formData.append('file', file)
        formData.append('folder_id', folderId)

        // Отслеживание прогресса
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100)
            onProgress(percentComplete)
          }
        })

        // Обработка завершения загрузки
        xhr.addEventListener('load', () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            try {
              const response = JSON.parse(xhr.responseText)
              resolve(response)
            } catch (e) {
              resolve(xhr.responseText)
            }
          } else {
            try {
              const errorData = JSON.parse(xhr.responseText)
              reject(new Error(errorData.message || `HTTP error! status: ${xhr.status}`))
            } catch (e) {
              reject(new Error(`HTTP error! status: ${xhr.status}`))
            }
          }
        })

        // Обработка ошибок
        xhr.addEventListener('error', () => {
          reject(new Error('Ошибка сети при загрузке файла'))
        })

        xhr.addEventListener('abort', () => {
          reject(new Error('Загрузка файла была отменена'))
        })

        // Получаем токен для авторизации
        const { getAuthHeader } = useAuthToken()
        const headers = getAuthHeader()
        const token = headers['Authorization']?.replace('Bearer ', '')

        // Открываем соединение
        xhr.open('POST', '/api/v1/media')
        
        // Устанавливаем заголовок авторизации
        if (token) {
          xhr.setRequestHeader('Authorization', `Bearer ${token}`)
        }
        xhr.setRequestHeader('Accept', 'application/json')

        // Отправляем запрос
        xhr.send(formData)
      })
    }

    // Загрузка файлов на сервер
    const handleUpload = async () => {
      if (selectedFiles.value.length === 0) {
        error.value = 'Выберите файлы для загрузки'
        return
      }

      if (!selectedFolder.value) {
        error.value = 'Выберите папку для загрузки'
        return
      }

      uploading.value = true
      error.value = null

      // Инициализируем прогресс
      uploadProgress.value = {
        total: selectedFiles.value.length,
        completed: 0
      }

      // Инициализируем прогресс для каждого файла
      selectedFiles.value.forEach(file => {
        file.uploadProgress = 0
      })

      try {
        const uploadPromises = selectedFiles.value.map(async (file, index) => {
          try {
            const result = await uploadFileWithProgress(
              file,
              selectedFolder.value.id,
              (progress) => {
                // Обновляем прогресс конкретного файла
                file.uploadProgress = progress
              }
            )

            // Увеличиваем счетчик завершенных загрузок
            uploadProgress.value.completed++
            file.uploadProgress = 100

            return result
          } catch (err) {
            // Увеличиваем счетчик даже при ошибке
            uploadProgress.value.completed++
            file.uploadProgress = 0
            file.uploadError = err.message
            throw err
          }
        })

        await Promise.all(uploadPromises)
        
        // Очищаем превью URL после успешной загрузки
        cleanupFileUrls()
        
        // Обновляем список файлов
        await fetchMediaFiles(selectedFolder.value.id)
        // Обновляем список папок для обновления счетчика
        await fetchFolders()
        
        selectedFiles.value = []
        uploadProgress.value = { total: 0, completed: 0 }
        console.log('[Media] Files uploaded successfully')
      } catch (err) {
        console.error('[Media] Error uploading files:', err)
        error.value = err.message || 'Ошибка загрузки файлов'
      } finally {
        uploading.value = false
      }
    }

    // Отмена загрузки
    const handleCancel = () => {
      cleanupFileUrls()
      selectedFiles.value = []
      error.value = null
      uploading.value = false
      uploadProgress.value = { total: 0, completed: 0 }
      console.log('[Media] Upload cancelled')
    }

    // Возврат к списку папок
    const handleBack = () => {
      selectedFolder.value = null
      mediaFiles.value = []
      nestedFolders.value = []
      breadcrumbs.value = []
      saveFolderToStorage(null)
      console.log('[Media] Back to folders list')
    }
    
    // Переход к папке из хлебных крошек
    const handleBreadcrumbClick = async (crumb) => {
      if (crumb.id === null) {
        // Переход в корневую папку
        handleBack()
      } else {
        // Переход к конкретной папке
        await handleFolderClick(crumb.folder)
      }
    }

    // Открытие/закрытие диалога создания папки (для корневого уровня)
    const handleToggleCreateFolder = () => {
      // Теперь сразу вызываем handleCreateFolder, который использует SweetAlert2
      handleCreateFolder()
    }

    // Проверка, можно ли просмотреть файл
    const isPreviewable = (file) => {
      // Фото и видео можно просмотреть в lightbox
      if (file.type === 'photo' || file.type === 'video') {
        return true
      }
      // Документы можно открыть в новой вкладке
      const previewableExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']
      return previewableExtensions.includes(file.extension?.toLowerCase())
    }

    // Открытие lightbox для просмотра фото/видео
    const openLightbox = (file) => {
      // Фильтруем только фото и видео из текущего списка
      const mediaFilesList = mediaFiles.value.filter(f => f.type === 'photo' || f.type === 'video')
      
      // Находим индекс текущего файла
      const currentIndex = mediaFilesList.findIndex(f => f.id === file.id)
      
      if (currentIndex === -1) {
        console.error('[Media] File not found in mediaFiles:', file)
        return
      }
      
      // Подготавливаем источники для lightbox
      const sources = mediaFilesList.map(f => f.url)
      
      console.log('[Media] Opening lightbox:', {
        currentIndex,
        slide: currentIndex + 1,
        totalSources: sources.length,
        file: file.original_name
      })
      
      // Устанавливаем параметры lightbox
      lightboxSources.value = sources
      lightboxSlide.value = currentIndex + 1 // fslightbox использует 1-based индексацию
      
      // Переключаем lightbox
      nextTick(() => {
        lightboxToggler.value = !lightboxToggler.value
      })
    }

    // Проверка, выбран ли файл
    const isFileSelected = (file) => {
      if (!props.selectedFiles || !Array.isArray(props.selectedFiles)) {
        return false
      }
      return props.selectedFiles.some(f => f.id === file.id)
    }

    // Открытие файла для просмотра (lightbox для фото/видео, новая вкладка для документов)
    const openFilePreview = (file) => {
      // Проверяем, открыта ли страница в режиме выбора через URL параметры (приоритет)
      const urlParams = new URLSearchParams(window.location.search)
      const selectMode = urlParams.get('select')
      const callback = urlParams.get('callback')
      
      if (selectMode === 'true' && callback) {
        // Сохраняем выбранный файл в localStorage
        try {
          localStorage.setItem(`media-selected-${callback}`, JSON.stringify({
            url: file.url,
            id: file.id,
            name: file.original_name,
            type: file.type
          }))
          
          // Отправляем сообщение родительскому окну, если оно есть
          if (window.opener && !window.opener.closed) {
            try {
              window.opener.postMessage({
                type: 'media-selected',
                callback: callback,
                url: file.url,
                id: file.id,
                name: file.original_name,
                type: file.type
              }, window.location.origin)
            } catch (e) {
              console.log('[Media] Could not send postMessage to opener:', e)
            }
          }
          
          // Закрываем окно после выбора
          setTimeout(() => {
            window.close()
          }, 100)
          return
        } catch (e) {
          console.error('[Media] Error saving to localStorage:', e)
        }
      }
      
      // Если включен режим выбора (через props), эмитим событие выбора файла
      if (props.selectionMode) {
        emit('file-selected', file)
        return
      }
      
      // Для фото и видео используем lightbox
      if (file.type === 'photo' || file.type === 'video') {
        openLightbox(file)
        return
      }
      
      // Для документов открываем в новой вкладке
      if (isPreviewable(file)) {
        const fullUrl = window.location.origin + file.url
        
        // Для PDF открываем напрямую
        if (file.extension?.toLowerCase() === 'pdf') {
          window.open(fullUrl, '_blank')
        }
        // Для Office документов можно использовать Google Docs Viewer или открыть напрямую
        else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(file.extension?.toLowerCase())) {
          // Открываем напрямую (браузер может предложить скачать или открыть)
          window.open(fullUrl, '_blank')
        }
        // Для текстовых файлов открываем напрямую
        else if (file.extension?.toLowerCase() === 'txt') {
          window.open(fullUrl, '_blank')
        }
      }
    }

    // Скачать файл
    const handleDownloadFile = (file) => {
      const fullUrl = window.location.origin + file.url
      const link = document.createElement('a')
      link.href = fullUrl
      link.download = file.original_name
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }

    // Редактировать файл (только для фото)
    const handleEditFile = (file) => {
      if (file.type !== 'photo') {
        return
      }
      selectedFileForEdit.value = file
      showImageEditor.value = true
    }

    // Обработка сохранения отредактированного изображения
    const handleImageSaved = async (savedFile) => {
      console.log('[Media] Image saved:', savedFile)
      // Обновляем список файлов
      if (selectedFolder.value) {
        await fetchMediaFiles(selectedFolder.value.id)
        await fetchFolders()
      }
    }

    // Загрузить все папки для выбора
    const fetchAllFolders = async () => {
      try {
        const response = await apiGet('/folders?paginate=0')
        if (!response.ok) {
          throw new Error('Ошибка загрузки папок')
        }
        const data = await response.json()
        
        // Обрабатываем разные форматы ответа
        let foldersList = []
        if (Array.isArray(data)) {
          foldersList = data
        } else if (data.data && Array.isArray(data.data)) {
          foldersList = data.data
        }
        
        // Фильтруем папки: исключаем текущую папку файла и корзину (id = 4)
        const currentFolderId = selectedFileForMove.value?.folder_id
        allFolders.value = foldersList.filter(folder => {
          // Исключаем текущую папку файла
          if (folder.id === currentFolderId) {
            return false
          }
          // Исключаем корзину (обычно id = 4, но проверяем и по is_trash если есть)
          if (folder.id === 4 || folder.is_trash === true) {
            return false
          }
          return true
        })
      } catch (err) {
        console.error('[Media] Error fetching all folders:', err)
        error.value = 'Ошибка загрузки списка папок'
      }
    }

    // Открыть модальное окно перемещения
    const handleMoveFile = async (file) => {
      selectedFileForMove.value = file
      selectedMoveFolderId.value = file.folder_id
      await fetchAllFolders()
      showMoveModal.value = true
    }

    // Выбрать папку для перемещения
    const selectMoveFolder = (folderId) => {
      selectedMoveFolderId.value = folderId
    }

    // Подтвердить перемещение файла
    const confirmMoveFile = async () => {
      if (!selectedFileForMove.value) {
        return
      }

      // Проверяем, что папка изменилась
      if (selectedMoveFolderId.value === selectedFileForMove.value.folder_id) {
        showMoveModal.value = false
        return
      }

      moving.value = true
      error.value = null

      try {
        // Отправляем как JSON, чтобы правильно передать null
        const requestData = {
          folder_id: selectedMoveFolderId.value === null ? null : selectedMoveFolderId.value
        }

        const response = await apiPut(`/media/${selectedFileForMove.value.id}`, requestData)

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка перемещения файла')
        }

        // Обновляем список файлов
        if (selectedFolder.value) {
          await fetchMediaFiles(selectedFolder.value.id, currentPage.value)
        }
        await fetchFolders()

        showMoveModal.value = false
        console.log('[Media] File moved successfully')
      } catch (err) {
        console.error('[Media] Error moving file:', err)
        error.value = err.message || 'Ошибка перемещения файла'
      } finally {
        moving.value = false
      }
    }

    // Восстановление файла из корзины
    const handleRestoreFile = async (file) => {
      // Подтверждение через SweetAlert2
      const result = await Swal.fire({
        title: 'Восстановить файл?',
        html: `Файл <strong>"${file.original_name}"</strong> будет восстановлен в исходную папку.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Восстановить',
        cancelButtonText: 'Отмена',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
      })

      if (!result.isConfirmed) {
        return
      }

      try {
        const response = await apiPost(`/media/${file.id}/restore`)

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка восстановления файла')
        }

        // Показываем уведомление об успехе
        Swal.fire({
          title: 'Файл восстановлен',
          html: `Файл <strong>"${file.original_name}"</strong> успешно восстановлен.`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        })

        // Обновляем список файлов
        if (selectedFolder.value) {
          await fetchMediaFiles(selectedFolder.value.id, currentPage.value)
        }
        // Обновляем список папок для обновления счетчика
        await fetchFolders()
        
        console.log('[Media] File restored successfully')
      } catch (err) {
        console.error('[Media] Error restoring file:', err)
        
        // Показываем ошибку
        Swal.fire({
          title: 'Ошибка',
          text: err.message || 'Ошибка восстановления файла',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        
        error.value = err.message || 'Ошибка восстановления файла'
      }
    }

    // Удаление файла
    const handleDeleteFile = async (file) => {
      // Для корзины - безвозвратное удаление с подтверждением
      if (isTrashFolder.value) {
        const result = await Swal.fire({
          title: 'Безвозвратно удалить файл?',
          html: `Файл <strong>"${file.original_name}"</strong> будет удалён навсегда и его нельзя будет восстановить.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Да, удалить навсегда',
          cancelButtonText: 'Отмена',
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          reverseButtons: true,
          focusCancel: true,
          customClass: {
            confirmButton: 'swal2-confirm-danger',
            cancelButton: 'swal2-cancel'
          }
        })

        if (!result.isConfirmed) {
          return
        }
      } else {
        // Для обычных папок - перемещение в корзину
        const result = await Swal.fire({
          title: 'Удалить файл?',
          html: `Файл <strong>"${file.original_name}"</strong> будет перемещён в корзину.`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Удалить',
          cancelButtonText: 'Отмена',
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          reverseButtons: true
        })

        if (!result.isConfirmed) {
          return
        }
      }

      try {
        const response = await apiDelete(`/media/${file.id}`)

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка удаления файла')
        }

        // Показываем уведомление об успехе
        Swal.fire({
          title: isTrashFolder.value ? 'Файл удалён' : 'Файл перемещён в корзину',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        })

        // Обновляем список файлов
        if (selectedFolder.value) {
          await fetchMediaFiles(selectedFolder.value.id, currentPage.value)
        }
        // Обновляем список папок для обновления счетчика
        await fetchFolders()
        
        console.log('[Media] File deleted successfully')
      } catch (err) {
        console.error('[Media] Error deleting file:', err)
        
        // Показываем ошибку
        Swal.fire({
          title: 'Ошибка',
          text: err.message || 'Ошибка удаления файла',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        
        error.value = err.message || 'Ошибка удаления файла'
      }
    }

    // Очистка всей корзины
    const handleClearTrash = async () => {
      if (!isTrashFolder.value || mediaFiles.value.length === 0) {
        return
      }

      const filesCount = mediaFiles.value.length
      
      // Подтверждение через SweetAlert2
      const result = await Swal.fire({
        title: 'Очистить всю корзину?',
        html: `Будут <strong>безвозвратно удалены</strong> все файлы (${filesCount} шт.) из корзины.<br><br>Это действие нельзя отменить!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `Да, удалить все (${filesCount})`,
        cancelButtonText: 'Отмена',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
          confirmButton: 'swal2-confirm-danger',
          cancelButton: 'swal2-cancel'
        }
      })

      if (!result.isConfirmed) {
        return
      }

      clearingTrash.value = true
      error.value = null

      try {
        // Удаляем все файлы последовательно
        let deletedCount = 0
        let errorCount = 0

        for (const file of mediaFiles.value) {
          try {
            const response = await apiDelete(`/media/${file.id}`)
            if (response.ok) {
              deletedCount++
            } else {
              errorCount++
            }
          } catch (err) {
            console.error(`[Media] Error deleting file ${file.id}:`, err)
            errorCount++
          }
        }

        // Показываем результат
        if (errorCount === 0) {
          Swal.fire({
            title: 'Корзина очищена',
            html: `Успешно удалено файлов: <strong>${deletedCount}</strong>`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          })
        } else {
          Swal.fire({
            title: 'Частично выполнено',
            html: `Удалено: <strong>${deletedCount}</strong><br>Ошибок: <strong>${errorCount}</strong>`,
            icon: 'warning',
            confirmButtonText: 'ОК'
          })
        }

        // Обновляем список файлов
        if (selectedFolder.value) {
          await fetchMediaFiles(selectedFolder.value.id, currentPage.value)
        }
        // Обновляем список папок для обновления счетчика
        await fetchFolders()
        
        console.log('[Media] Trash cleared:', { deletedCount, errorCount })
      } catch (err) {
        console.error('[Media] Error clearing trash:', err)
        
        Swal.fire({
          title: 'Ошибка',
          text: err.message || 'Ошибка очистки корзины',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        
        error.value = err.message || 'Ошибка очистки корзины'
      } finally {
        clearingTrash.value = false
      }
    }

    // Удаление папки
    const handleDeleteFolder = async (folder) => {
      // Проверяем, защищена ли папка
      if (folder.protected) {
        Swal.fire({
          title: 'Ошибка',
          text: 'Нельзя удалить защищенную папку',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        return
      }

      // Если мы в корзине - безвозвратное удаление
      if (isTrashFolder.value) {
        const result = await Swal.fire({
          title: 'Удалить навсегда?',
          html: `Вы уверены, что хотите <strong>безвозвратно удалить</strong> папку <strong>"${folder.name}"</strong> и всё её содержимое?<br><br>Это действие нельзя отменить!`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Да, удалить навсегда',
          cancelButtonText: 'Отмена'
        })

        if (!result.isConfirmed) {
          return
        }

        loading.value = true
        error.value = null

        try {
          // Для безвозвратного удаления используем forceDelete
          const response = await apiDelete(`/folders/${folder.id}?force=1`)

          if (!response.ok) {
            const errorData = await response.json()
            throw new Error(errorData.message || 'Ошибка удаления папки')
          }

          // Обновляем список вложенных папок в корзине
          if (selectedFolder.value) {
            await fetchNestedFolders(selectedFolder.value.id)
          }
          
          Swal.fire({
            title: 'Папка удалена',
            html: `Папка <strong>"${folder.name}"</strong> безвозвратно удалена.`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          })
          
          console.log('[Media] Folder permanently deleted')
        } catch (err) {
          console.error('[Media] Error deleting folder:', err)
          
          Swal.fire({
            title: 'Ошибка',
            text: err.message || 'Ошибка удаления папки',
            icon: 'error',
            confirmButtonText: 'ОК'
          })
          
          error.value = err.message || 'Ошибка удаления папки'
        } finally {
          loading.value = false
        }
        
        return
      }

      // Обычное удаление (перемещение в корзину)
      const result = await Swal.fire({
        title: 'Удалить папку?',
        html: `Вы уверены, что хотите удалить папку <strong>"${folder.name}"</strong> и всё её содержимое?<br><br>Папка будет перемещена в корзину.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Да, удалить',
        cancelButtonText: 'Отмена'
      })

      if (!result.isConfirmed) {
        return
      }

      loading.value = true
      error.value = null

      try {
        const response = await apiDelete(`/folders/${folder.id}`)

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка удаления папки')
        }

        // Обновляем список папок
        await fetchFolders()
        
        // Если мы внутри папки, обновляем список вложенных папок
        if (selectedFolder.value) {
          await fetchNestedFolders(selectedFolder.value.id)
        }
        
        // Показываем уведомление об успехе
        Swal.fire({
          title: 'Папка удалена',
          html: `Папка <strong>"${folder.name}"</strong> перемещена в корзину.`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        })
        
        console.log('[Media] Folder deleted successfully')
      } catch (err) {
        console.error('[Media] Error deleting folder:', err)
        
        Swal.fire({
          title: 'Ошибка',
          text: err.message || 'Ошибка удаления папки',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        
        error.value = err.message || 'Ошибка удаления папки'
      } finally {
        loading.value = false
      }
    }

    // Восстановление папки из корзины
    const handleRestoreFolder = async (folder) => {
      const result = await Swal.fire({
        title: 'Восстановить папку?',
        html: `Вы уверены, что хотите восстановить папку <strong>"${folder.name}"</strong> и всё её содержимое?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Да, восстановить',
        cancelButtonText: 'Отмена'
      })

      if (!result.isConfirmed) {
        return
      }

      loading.value = true
      error.value = null

      try {
        const response = await apiPost(`/folders/${folder.id}/restore`)

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка восстановления папки')
        }

        // Обновляем список вложенных папок в корзине
        if (selectedFolder.value) {
          await fetchNestedFolders(selectedFolder.value.id)
        }
        
        // Обновляем список корневых папок
        await fetchFolders()
        
        Swal.fire({
          title: 'Папка восстановлена',
          html: `Папка <strong>"${folder.name}"</strong> успешно восстановлена.`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        })
        
        console.log('[Media] Folder restored successfully')
      } catch (err) {
        console.error('[Media] Error restoring folder:', err)
        
        Swal.fire({
          title: 'Ошибка',
          text: err.message || 'Ошибка восстановления папки',
          icon: 'error',
          confirmButtonText: 'ОК'
        })
        
        error.value = err.message || 'Ошибка восстановления папки'
      } finally {
        loading.value = false
      }
    }

    // Загружаем папки при монтировании
    onMounted(async () => {
      console.log('[Media] onMounted() вызван')
      await fetchFolders()
      
      // Пытаемся восстановить папку из localStorage
      const restored = await loadFolderFromStorage()
      if (!restored) {
        // Если не удалось восстановить, показываем корневые папки
        selectedFolder.value = null
        breadcrumbs.value = []
      }
    })

    // Очистка при размонтировании компонента
    onBeforeUnmount(() => {
      cleanupFileUrls()
      // Очищаем таймер поиска
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
    })

    return {
      selectionMode: props.selectionMode,
      isSelectionModeActive,
      isFileSelected,
      loading,
      loadingMedia,
      uploading,
      error,
      searchQuery,
      folders,
      selectedFolder,
      newFolderName,
      isCreateFolderOpen,
      selectedFiles,
      mediaFiles,
      nestedFolders,
      breadcrumbs,
      filteredFolders,
      isTrashFolder,
      canUpload,
      uploadProgress,
      isDragging,
      isDraggingButton,
      lightboxToggler,
      lightboxSources,
      lightboxSlide,
      isPreviewable,
      openLightbox,
      openFilePreview,
      handleDownloadFile,
      handleEditFile,
      handleMoveFile,
      handleRestoreFile,
      showImageEditor,
      selectedFileForEdit,
      handleImageSaved,
      showMoveModal,
      selectedFileForMove,
      selectedMoveFolderId,
      allFolders,
      moving,
      clearingTrash,
      selectMoveFolder,
      confirmMoveFile,
      handleBack,
      handleBreadcrumbClick,
      handleToggleCreateFolder,
      handleCreateFolder,
      handleFolderClick,
      fileInput,
      handleFileButtonClick,
      handleFileSelect,
      handleDrop,
      handleDropOnButton,
      handleImageError,
      getDefaultFolderIcon,
      getFolderIcon,
      handleFolderImageError,
      getFileIcon,
      formatFileSize,
      isImageFile,
      isVideoFile,
      getFileExtension,
      getFilePreview,
      getFileIconFromFile,
      handleUpload,
      handleCancel,
      handleDeleteFile,
      handleClearTrash,
      handleDeleteFolder,
      handleRestoreFolder,
      // Пагинация и фильтрация
      currentPage,
      perPage,
      perPageOptions,
      totalFiles,
      lastPage,
      fileSearchQuery,
      fileTypeFilter,
      fileSortBy,
      fileSortOrder,
      sortOptions,
      currentSortLabel,
      paginationData,
      handlePageChange,
      handlePerPageChange,
      handleFileSearch,
      handleTypeFilter,
      handleSortChange,
      getPageNumbers
    }
  }
}
</script>

