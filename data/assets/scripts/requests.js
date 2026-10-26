document.addEventListener('DOMContentLoaded', function() {
    const questionItems = document.querySelectorAll('.question__question-item');

    questionItems.forEach(item => {
        item.addEventListener('click', function() {
            const isActive = item.classList.contains('menu-open');

            // Закрываем все открытые элементы
            document.querySelectorAll('.question__question-item').forEach(el => {
                el.classList.remove('menu-open');
            });
            document.querySelectorAll('.requests__content').forEach(el => {
                el.classList.remove('menu_open');
            });

            // Если элемент не был активен, открываем его
            if (!isActive) {
                item.classList.add('menu-open');
                item.nextElementSibling.classList.add('menu_open');
            }
        });
    });
});
