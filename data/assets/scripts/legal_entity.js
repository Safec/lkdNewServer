document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form"); // Указываем нужную форму
    const submitButton = form.querySelector("button[type=submit]");

    function checkRequiredFields() {
        let allFilled = true;

        form.querySelectorAll("[required]").forEach(field => {
            if (!field.offsetParent && field.type !== "hidden") return; // Пропускаем скрытые поля
            if (!field.value.trim()) {
                allFilled = false;
            }
        });

        if (allFilled) {
            submitButton.removeAttribute("disabled");
            submitButton.setAttribute("data-form-required", "true");
        } else {
            submitButton.setAttribute("disabled", "disabled");
            submitButton.setAttribute("data-form-required", "false");
        }
    }

    // Проверять при изменении полей
    form.addEventListener("change", checkRequiredFields);

    // Проверить при загрузке страницы (если вдруг поля уже заполнены)
    checkRequiredFields();



    const legalEntity = document.querySelector("#legal-entity select");
    const taxSystemWrapper = document.getElementById("tax-system-wrapper");
    const taxSystem = document.querySelector("#tax-system-wrapper select");
    const vatRateWrapper = document.getElementById("vat-rate-wrapper");
    const vatRate = document.querySelector("#vat-rate-wrapper select");

    function updateVisibility() {
        if (legalEntity.value === "ООО" || legalEntity.value === "ИП") {
            taxSystemWrapper.style.display = "block";
            vatRateWrapper.style.display = "block";
            taxSystem.setAttribute('required', 'required');
            vatRate.setAttribute('required', 'required');
        } else {
            taxSystemWrapper.style.display = "none";
            taxSystem.value = "";
            vatRateWrapper.style.display = "none";
            vatRate.innerHTML = '<option value="">Выберите...</option>';
            taxSystem.removeAttribute('required');
            vatRate.removeAttribute('required');
        }
    }

    function updateVatOptions() {
        vatRate.innerHTML = '<option value="">Выберите...</option>';

        // Для ИП
        if (legalEntity.value === "ИП") {
            if (taxSystem.value === "usn") {
                vatRate.innerHTML += ` 
                    <option value="no_vat">БЕЗ НДС</option>
                    <option value="vat_5">НДС 5%</option>
                    <option value="vat_7">НДС 7%</option>
                `;
            } else if (taxSystem.value === "osno") {
                vatRate.innerHTML += `
                    <option value="with_vat" selected="selected">С НДС</option>
                `;
            }
        }
        // Для ООО
        else if (legalEntity.value === "ООО") {
            if (taxSystem.value === "usn") {
                vatRate.innerHTML += `
                    <option value="no_vat" selected="selected">БЕЗ НДС</option>
                `;
            } else if (taxSystem.value === "osno") {
                vatRate.innerHTML += `
                    <option value="with_vat" selected="selected">С НДС</option>
                `;
            }
        }
        // Если налоговая система не выбрана или для других типов, скрываем ставки НДС
        else {
            vatRateWrapper.style.display = "none";
        }

    }


    // Слушаем изменения для типа юридического лица
    if (legalEntity) {
        legalEntity.addEventListener("change", function () {
            updateVisibility(); // Обновляем видимость для налоговой системы и НДС
            updateVatOptions(); // Обновляем ставки НДС в зависимости от системы
        });
    }

    if (taxSystem)
        taxSystem.addEventListener("change", updateVatOptions);

    if (legalEntity) {
        updateVisibility(); // Для обработки при загрузке страницы
    }
});