/**
 * Скрипт для сбора данных через браузер MCP
 * Использует браузерные инструменты для прямого доступа к страницам
 */

const fs = require('fs');
const path = require('path');

const DATA_FILE = path.join(__dirname, 'storage', 'pirogi_data.json');

// Загружаем данные
if (!fs.existsSync(DATA_FILE)) {
    console.error(`❌ Файл не найден: ${DATA_FILE}`);
    process.exit(1);
}

const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));

console.log('═══════════════════════════════════════');
console.log('Сбор данных через браузер MCP');
console.log('═══════════════════════════════════════\n');
console.log('Этот скрипт требует использования браузерных инструментов MCP.');
console.log('Используйте HTML скрипт scrape_nutritional_data_local.html вместо этого.\n');
console.log('Или соберите данные вручную через консоль браузера.\n');

// Показываем инструкцию
console.log('Инструкция:');
console.log('1. Откройте scrape_nutritional_data_local.html в браузере');
console.log('2. Скопируйте содержимое storage/pirogi_data.json');
console.log('3. Вставьте в HTML скрипт и запустите сбор');
console.log('4. Скопируйте обновленный JSON обратно в файл\n');

process.exit(0);
