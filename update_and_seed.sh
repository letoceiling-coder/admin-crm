#!/bin/bash

# Скрипт для автоматического обновления и запуска seeder
# Использование: ./update_and_seed.sh

echo "═══════════════════════════════════════"
echo "Обновление товаров с питательными веществами"
echo "═══════════════════════════════════════"
echo ""

# Проверяем наличие JSON файла
if [ ! -f "storage/pirogi_data.json" ]; then
    echo "❌ Файл storage/pirogi_data.json не найден!"
    exit 1
fi

# Проверяем данные о питательных веществах
WITH_NUTRITION=$(php -r "
\$data = json_decode(file_get_contents('storage/pirogi_data.json'), true);
\$count = 0;
foreach (\$data['products'] ?? [] as \$product) {
    if (isset(\$product['weight']) || isset(\$product['protein'])) {
        \$count++;
    }
}
echo \$count;
")

TOTAL=$(php -r "
\$data = json_decode(file_get_contents('storage/pirogi_data.json'), true);
echo count(\$data['products'] ?? []);
")

echo "📦 Всего товаров: $TOTAL"
echo "✅ Товаров с данными: $WITH_NUTRITION"
echo "⚠️  Товаров без данных: $((TOTAL - WITH_NUTRITION))"
echo ""

if [ "$WITH_NUTRITION" -eq 0 ]; then
    echo "⚠️  ВНИМАНИЕ: Нет данных о питательных веществах!"
    echo "   Seeder обновит товары, но поля останутся пустыми."
    echo ""
    read -p "Продолжить? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Отменено."
        exit 1
    fi
fi

# Выполняем миграции
echo ""
echo "🔄 Выполнение миграций..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Ошибка при выполнении миграций!"
    exit 1
fi

echo "✅ Миграции выполнены"
echo ""

# Запускаем seeder
echo "🔄 Запуск seeder..."
php artisan db:seed --class=PirogiSeeder

if [ $? -ne 0 ]; then
    echo "❌ Ошибка при выполнении seeder!"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════"
echo "✅ Готово! Товары обновлены."
echo "═══════════════════════════════════════"
