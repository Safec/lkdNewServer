var init = function() {
    document.querySelectorAll(".conditions__content .question__question-item__zagolovok").forEach((element) => {
        element.addEventListener("click", () => {
            element.parentElement.classList.toggle("active");
        });
    });

    // Обработчик для иконки бургер-меню
    if (document.getElementById("burgerIcon")) {
        document.getElementById("burgerIcon").onclick = function() {
            document.querySelector(".sidebar__nav").classList.remove("hiddenMobile");
        };
    }

    // Обработчик для закрытия меню
    if (document.getElementById("closeMenu")) {
        document.getElementById("closeMenu").onclick = function() {
            document.querySelector(".sidebar__nav").classList.add("hiddenMobile");
        };
    }
};

function updateModels(subcategoryIds) {
    let requestData = { subcategoryIds };
    console.log(subcategoryIds);

    fetch("/catalog/models/update", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(requestData)
    })
        .then(response => response.json())
        .then(data => {
            document.querySelector(".models").innerHTML = data.modelsHtml;
            document.querySelector(".pagingM").style.display = "none";
        })
        .catch(error => console.error("Error:", error));
}

document.addEventListener("DOMContentLoaded", () => {
    let items = document.querySelectorAll(".carousel__item");
    let carouselInner = document.querySelector(".carousel__inner");
    let prevButton = document.querySelector(".carousel__nav--prev");
    let nextButton = document.querySelector(".carousel__nav--next");

    if (items.length <= 1) {
        if (prevButton)
            prevButton.classList.add("hidden");
        if (nextButton)
            nextButton.classList.add("hidden");
    }

    let currentIndex = 0;
    let startX = 0;
    let endX = 0;
    let isSwiping = false;

    function moveSlide(offset) {
        currentIndex += offset;
        if (currentIndex < 0) currentIndex = items.length - 1;
        if (currentIndex >= items.length) currentIndex = 0;
        carouselInner.style.transform = `translateX(-${100 * currentIndex}%)`;
    }

    if (carouselInner) {
        carouselInner.addEventListener("touchstart", (e) => {
            startX = e.touches[0].clientX;
            isSwiping = true;
        });

        carouselInner.addEventListener("touchmove", (e) => {
            if (isSwiping) endX = e.touches[0].clientX;
        });

        carouselInner.addEventListener("touchend", () => {
            if (isSwiping) {
                let delta = startX - endX;
                if (delta > 50) moveSlide(1);
                if (delta < -50) moveSlide(-1);
                isSwiping = false;
            }
        });
    }

    if (prevButton)
        prevButton.addEventListener("click", () => moveSlide(-1));
    if (nextButton)
        nextButton.addEventListener("click", () => moveSlide(1));
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('[data-action-name="onChangeCategory"]').forEach((element) => {
        element.addEventListener("click", (event) => {
            event.preventDefault();

            let categoryId = element.getAttribute("data-category-id");
            let activeElement = document.querySelector(".category-list li.active");

            if (activeElement) activeElement.classList.remove("active");
            event.target.parentElement.classList.add("active");

            fetch(`/catalog/${categoryId}/subcategories`)
                .then(response => response.json())
                .then(subcategories => {
                    let subcategoryList = document.getElementById("subcategories");
                    subcategoryList.innerHTML = "";

                    subcategories.forEach((subcategory) => {
                        let li = document.createElement("li");
                        li.classList.add("active");
                        li.innerHTML = `<a href="#" data-subcategory-id="${subcategory.id}">${subcategory.name}</a>`;
                        subcategoryList.appendChild(li);
                    });

                    document.querySelectorAll("#subcategories li").forEach((li) => {
                        li.addEventListener("click", (e) => {
                            e.preventDefault();

                            if (!li.classList.contains("active") || document.querySelectorAll("#subcategories li.active").length > 1) {
                                li.classList.toggle("active");

                                let selectedIds = Array.from(document.querySelectorAll("#subcategories li.active"))
                                    .map(li => li.querySelector("a").getAttribute("data-subcategory-id"));

                                updateModels(selectedIds);
                            }
                        });
                    });

                    let selectedIds = Array.from(document.querySelectorAll("#subcategories li.active"))
                        .map(li => li.querySelector("a").getAttribute("data-subcategory-id"));

                    if (selectedIds.length > 0) updateModels(selectedIds);
                })
                .catch(error => console.error("Ошибка:", error));
        });
    });
});


if (document.readyState !== "loading") {
    init();
} else {
    document.addEventListener("DOMContentLoaded", init);
}

