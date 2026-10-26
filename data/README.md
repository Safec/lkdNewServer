### Вёрстка

Все файлы вёрстки находятся в папке `assets`.

#### Структура папок

* `assets/` — папка с файлами вёрстки.
  * `assets/fonts/` — папка со шрифтами. Шрифты нужно подключать в файле `assets/styles/fonts.css`.
  * `assets/html/` — папка с HTML-файлами вёрстки `*.html`. Сюда нужно сохранять просто статичные HTML-страницы.
    Их можно просматривать в браузере, запустив сервер разработки (см. ниже). Вёрстка из этих файлов будет внедряться
    в шаблоны Twig на следующем этапе разработке.
  * `assets/images/` — папка с изображениями. Собираются в папку `build/images/`.
  * `assets/scripts/` — папка со скриптами `*.js`. Собираются в файл `build/app.js`.
  * `assets/styles/` — папка со стилями `*.css` или `*.scss`. Собираются в файл `build/app.css`. 
  * `app.js` — файл, в котором нужно подключать все скрипты и стили.

#### Окружение

* [Node.js v18](https://nodejs.org)
* [NPM v9](https://www.npmjs.com)

#### Установка

```bash
npm install
```

#### Сервер разработки

Запустить сервер разработки можно командой:
```bash
npm run dev-server
```

После запуска сервера разработки, проект будет доступен по адресу [http://localhost:8080](http://localhost:8080).
По этому адресу можно просматривать HTML-файлы вёрстки из папки `assets/html/`.

В HTML-файлах вёрстки в папке `assets/html/` должны быть подключены скрипты и стили из папки `assets/html/build/`.
Например:
```html
<link rel="stylesheet" href="build/app.css">
<script src="build/app.js" defer></script>
```

Сервер разработки будет отслеживать изменения в файлах вёрстки `assets/` и автоматически пересобирать проект в папку
`assets/html/build/`.

#### Tailwind CSS

В проекте используется фреймворк [Tailwind CSS](https://tailwindcss.com). Современные версии 
[PhpStorm](https://www.jetbrains.com/phpstorm/) и [WebStorm](https://www.jetbrains.com/webstorm/)
поддерживают автокомплит для [Tailwind CSS](https://tailwindcss.com). В [Visual Studio Code](https://code.visualstudio.com)
можно установить расширение [Tailwind CSS IntelliSense](https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss).
