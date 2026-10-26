const onReady = function () {
    const questionItems = document.querySelectorAll(".question__question-item__zagolovok");

    questionItems.forEach(item => {
        item.addEventListener("click", () => {
            const parentItem = item.parentElement;
            parentItem.classList.toggle("active");
        });
    });
}

if (document.readyState !== 'loading') {
    onReady();
} else {
    document.addEventListener('DOMContentLoaded', onReady);
}