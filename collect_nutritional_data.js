/**
 * Скрипт для сбора питательных веществ с pirogi.ru
 * Использование: node collect_nutritional_data.js
 * 
 * Требования: npm install puppeteer
 */

const fs = require('fs');
const path = require('path');

// Проверяем наличие puppeteer
let puppeteer;
try {
    puppeteer = require('puppeteer');
} catch (e) {
    console.error('❌ Puppeteer не установлен!');
    console.error('Установите: npm install puppeteer');
    process.exit(1);
}

const DATA_FILE = path.join(__dirname, 'storage', 'pirogi_data.json');
const BASE_URL = 'https://pirogi.ru';

async function extractNutritionalData(page, productName) {
    try {
        // Пробуем найти товар через прямой поиск на главной странице
        // Сначала загружаем главную страницу
        await page.goto(BASE_URL, {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(3000);

        // Ищем товар на странице (может быть в каталоге)
        let productLink = await page.evaluate((name) => {
            // Ищем по точному названию
            const links = Array.from(document.querySelectorAll('a'));
            for (const link of links) {
                const text = link.textContent.trim();
                if (text === name || text.includes(name) || name.includes(text)) {
                    const href = link.href;
                    if (href && href.includes('/shop/') || href.includes('/product/') || href.includes('/id/')) {
                        return href;
                    }
                }
            }
            return null;
        }, productName);

        // Если не нашли, пробуем через поиск
        if (!productLink) {
            try {
                await page.goto(`${BASE_URL}/?search=${encodeURIComponent(productName)}`, {
                    waitUntil: 'domcontentloaded',
                    timeout: 60000
                });

                await page.waitForTimeout(3000);

                productLink = await page.evaluate((name) => {
                    const links = Array.from(document.querySelectorAll('a'));
                    for (const link of links) {
                        const text = link.textContent.trim();
                        if (text === name || text.includes(name) || name.includes(text)) {
                            const href = link.href;
                            if (href && (href.includes('/shop/') || href.includes('/product/') || href.includes('/id/'))) {
                                return href;
                            }
                        }
                    }
                    return null;
                }, productName);
            } catch (e) {
                // Игнорируем ошибки поиска
            }
        }

        if (!productLink) {
            return null;
        }

        // Переходим на страницу товара
        await page.goto(productLink, {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(3000);

        // Извлекаем данные
        const data = await page.evaluate(() => {
            const result = {};

            // Вес
            const weightEl = document.querySelector('.id-weight');
            if (weightEl) {
                const match = weightEl.textContent.match(/(\d+)\s*гр?/i);
                if (match) {
                    result.weight = parseInt(match[1]);
                }
            }

            // Питательные вещества
            const paramItems = document.querySelectorAll('.id-params-item');
            paramItems.forEach(item => {
                const titleEl = item.querySelector('.title');
                const valueEl = item.querySelector('.value');
                
                if (titleEl && valueEl) {
                    const title = titleEl.textContent.trim().toLowerCase();
                    const valueText = valueEl.textContent.trim();
                    const match = valueText.match(/([\d.]+)/);
                    
                    if (match) {
                        const value = parseFloat(match[1]);
                        
                        if (title.includes('белк')) {
                            result.protein = value;
                        } else if (title.includes('жир')) {
                            result.fat = value;
                        } else if (title.includes('углевод')) {
                            result.carbs = value;
                        } else if (title.includes('калори')) {
                            result.calories = parseInt(value);
                        }
                    }
                }
            });

            return Object.keys(result).length > 0 ? result : null;
        });

        return data;
    } catch (error) {
        // Игнорируем ошибки таймаута и навигации - просто возвращаем null
        return null;
    }
}

async function main() {
    console.log('═══════════════════════════════════════');
    console.log('Сбор питательных веществ с pirogi.ru');
    console.log('═══════════════════════════════════════\n');

    // Загружаем данные
    if (!fs.existsSync(DATA_FILE)) {
        console.error(`❌ Файл не найден: ${DATA_FILE}`);
        process.exit(1);
    }

    const data = JSON.parse(fs.readFileSync(DATA_FILE, 'utf8'));
    if (!data.products) {
        console.error('❌ Неверный формат данных');
        process.exit(1);
    }

    console.log(`📦 Найдено товаров: ${data.products.length}\n`);

    // Запускаем браузер
    console.log('🌐 Запуск браузера...');
    const browser = await puppeteer.launch({
        headless: 'new',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--disable-gpu'
        ],
        timeout: 60000
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    let updated = 0;
    let errors = 0;

    // Обрабатываем каждый товар
    for (let i = 0; i < data.products.length; i++) {
        const product = data.products[i];
        const productName = product.name;
        
        // Пропускаем если данные уже есть
        if (product.weight || product.protein) {
            console.log(`[${i + 1}/${data.products.length}] ⏭ ${productName} (уже есть данные)`);
            continue;
        }

        console.log(`[${i + 1}/${data.products.length}] 🔍 ${productName}...`);
        
        const nutritionalData = await extractNutritionalData(page, productName);
        
        if (nutritionalData) {
            Object.assign(product, nutritionalData);
            updated++;
            
            const info = [];
            if (nutritionalData.weight) info.push(`вес: ${nutritionalData.weight}г`);
            if (nutritionalData.protein) info.push(`Б: ${nutritionalData.protein}`);
            if (nutritionalData.fat) info.push(`Ж: ${nutritionalData.fat}`);
            if (nutritionalData.carbs) info.push(`У: ${nutritionalData.carbs}`);
            if (nutritionalData.calories) info.push(`ккал: ${nutritionalData.calories}`);
            
            console.log(`  ✓ ${info.join(', ')}`);
        } else {
            errors++;
            console.log(`  ⚠ Данные не найдены (можно добавить вручную позже)`);
        }
        
        // Сохраняем прогресс каждые 10 товаров
        if ((i + 1) % 10 === 0) {
            fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');
            console.log(`  💾 Промежуточное сохранение (${i + 1}/${data.products.length})`);
        }

        // Небольшая задержка между запросами (увеличена для стабильности)
        await new Promise(resolve => setTimeout(resolve, 3000));
    }

    await browser.close();

    // Сохраняем обновленные данные
    fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');

    console.log('\n═══════════════════════════════════════');
    console.log('✅ Готово!');
    console.log(`📊 Обновлено товаров: ${updated}`);
    console.log(`❌ Ошибок: ${errors}`);
    console.log(`💾 Данные сохранены в: ${DATA_FILE}`);
    console.log('═══════════════════════════════════════\n');
    console.log('Теперь можно запускать seeder на сервере!');
}

main().catch(console.error);
