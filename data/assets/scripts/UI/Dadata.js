import IMask from "imask";

export default class Dadata {
    static async fetchInnData(inn) {
        try {
            const response = await fetch('/ajax/dadata/inn', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({inn: inn})
            });

            return await response.json();
        } catch (e) {
            console.error(e);
            return {};
        }
    }

    static setInputValue($input, value) {
        const mask = $input.getAttribute('data-mask');
        if (typeof mask === 'string' && mask.length > 0) {
            let masked = IMask.createMask({mask: mask});
            masked.resolve(value);
            $input.value = masked.value;
        } else {
            $input.value = value;
        }

        $input.dispatchEvent(new Event('change'));
        $input.dispatchEvent(new Event('blur'));
    }

    static onChangeInn($input) {
        let inn = $input.value;
        if (inn.length === 10 || inn.length === 12) {
            const data = this.fetchInnData(inn).then(function (data) {
                if (!data) {
                    return;
                }

                const $form = $input.closest('form');

                if ((data['name'] || null) && (data['name']['full_with_opf'] || null)) {
                    $form.querySelectorAll('input[name*="[company]"]').forEach($input => Dadata.setInputValue($input, data['name']['short_with_opf']));
                }

                if (data['kpp'] || null) {
                    $form.querySelectorAll('input[name*="[KPP]"]').forEach($input => Dadata.setInputValue($input, data['kpp']));
                }

                if (data['ogrn'] || null) {
                    $form.querySelectorAll('input[name*="[OGRN]"]').forEach($input => Dadata.setInputValue($input, data['ogrn']));
                }

                if ((data['address'] || null) && (data['address']['value'] || null)) {
                    $form.querySelectorAll('input[name*="[address]"]').forEach($input => Dadata.setInputValue($input, data['address']['value']));
                    $form.querySelectorAll('input[name*="[legalAddress]"]').forEach($input => Dadata.setInputValue($input, data['address']['value']));
                    $form.querySelectorAll('input[name*="[mailingAddress]"]').forEach($input => Dadata.setInputValue($input, data['address']['value']));
                }
            });
        }
    }
}
