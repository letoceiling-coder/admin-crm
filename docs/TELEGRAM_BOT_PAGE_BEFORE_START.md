# Оформление страницы бота до команды /start

Страница, которую пользователь видит **до нажатия «Старт»** (до отправки `/start`), настраивается через **Telegram Bot API**:

- **Краткое описание** (short description) — до 120 символов. Показывается на карточке бота и при пересылке ссылки.
- **Полное описание** (description) — до 512 символов. Текст «О чём этот бот» / «Что умеет этот бот».

Официальные методы API:
- [setMyShortDescription](https://core.telegram.org/bots/api#setmyshortdescription)
- [setMyDescription](https://core.telegram.org/bots/api#setmydescription)
- [getMyShortDescription](https://core.telegram.org/bots/api#getmyshortdescription)
- [getMyDescription](https://core.telegram.org/bots/api#getmydescription)

## Как это реализовано в CRM

1. В карточке магазина (редактирование магазина) добавлен блок **«Оформление страницы бота (до нажатия «Старт»)»**:
   - поле **Краткое описание** (до 120 символов);
   - поле **Полное описание** (до 512 символов).

2. При сохранении магазина, если указан токен бота и заполнены эти поля, CRM вызывает `setMyShortDescription` и `setMyDescription` с языком `ru`. Текст на странице бота до /start обновляется автоматически.

3. Имя бота и аватар задаются в **BotFather** (или через соответствующие методы API, если потребуется). Кнопка «СТАРТ» — стандартная кнопка Telegram, её текст и вид менять нельзя.

## Альтернатива: настройка через BotFather

Описание и имя бота можно задать вручную в [@BotFather](https://t.me/BotFather):

- `/setdescription` — полное описание;
- `/setabouttext` — краткий текст «О боте»;
- `/setname` — имя бота.

Настройки в CRM перезаписывают эти значения при сохранении магазина (если токен бота указан и поля заполнены).
