/**
 * Упрощенный скрипт для сбора питательных веществ
 * Использует более простой подход - ищет товары по категориям
 */

const fs = require('fs');
const path = require('path');

let puppeteer;
try {
    puppeteer = require('puppeteer');
} catch (e) {
    console.error('❌ Puppeteer не установлен!');
    console.error('Установите: npm install puppeteer');
    process.exit(1);
}

const DATA_FILE = path.join(__dirname, 'storage', 'pirogi_data.json');

// Карта категорий для прямого перехода
const CATEGORY_URLS = {
    'Салаты': 'https://pirogi.ru/salaty/',
    'Супы': 'https://pirogi.ru/supy/',
    'Вторые блюда': 'https://pirogi.ru/vtorye-blyuda/',
    'Гарниры': 'https://pirogi.ru/garniry/',
    'Пироги сытные': 'https://pirogi.ru/pirogi-sytnye/',
    'Пироги сладкие': 'https://pirogi.ru/pirogi-sladkie/',
    'Выпечка': 'https://pirogi.ru/vypechka/',
    'Блины': 'https://pirogi.ru/bliny/',
};

async function extractFromPage(page, productName) {
    try {
        const data = await page.evaluate((name) => {
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
        }, productName);

        return data;
    } catch (e) {
        return null;
    }
}

async function findProductInCategory(page, categoryUrl, productName) {
    try {
        await page.goto(categoryUrl, {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(2000);

        // Ищем ссылку на товар
        const productLink = await page.evaluate((name) => {
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

        if (!productLink) {
            return null;
        }

        // Переходим на страницу товара
        await page.goto(productLink, {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(2000);

        return await extractFromPage(page, productName);
    } catch (e) {
        return null;
    }
}

async function main() {
    console.log('═══════════════════════════════════════');
    console.log('Сбор питательных веществ (упрощенная версия)');
    console.log('═══════════════════════════════════════\n');

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

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        timeout: 60000
    });
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    let updated = 0;
    let errors = 0;
    let currentCategory = null;
    let currentCategoryUrl = null;

    for (let i = 0; i < data.products.length; i++) {
        const product = data.products[i];
        
        if (product.weight || product.protein) {
            console.log(`[${i + 1}/${data.products.length}] ⏭ ${product.name} (уже есть)`);
            continue;
        }

        console.log(`[${i + 1}/${data.products.length}] 🔍 ${product.name}...`);

        // Если категория изменилась, загружаем страницу категории
        if (currentCategory !== product.categoryName) {
            currentCategory = product.categoryName;
            currentCategoryUrl = CATEGORY_URLS[product.categoryName] || null;
        }

        let nutritionalData = null;

        // Пробуем найти через страницу категории
        if (currentCategoryUrl) {
            nutritionalData = await findProductInCategory(page, currentCategoryUrl, product.name);
        }

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
            console.log(`  ⚠ Не найдено`);
        }

        // Сохраняем каждые 5 товаров
        if ((i + 1) % 5 === 0) {
            fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');
        }

        await new Promise(resolve => setTimeout(resolve, 2000));
    }

    await browser.close();
    fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf8');

    console.log('\n═══════════════════════════════════════');
    console.log('✅ Готово!');
    console.log(`📊 Обновлено: ${updated}`);
    console.log(`❌ Ошибок: ${errors}`);
    console.log('═══════════════════════════════════════\n');
}

main().catch(console.error);
