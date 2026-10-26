import TomSelect from 'tom-select';

export default class BubbleSelect {
    static init() {
        try {
            if (document.querySelector('.bubble-select')) {
                const tomSelect = new TomSelect(".bubble-select", {
                    maxItems: 5,
                    maxOptions: null,
                    persist: false,
                    plugins: {
                        remove_button: {
                            title: 'Удалить',
                        }
                    },
                });

                tomSelect.on('blur', function () {
                    try {
                        this.wrapper.classList.add('ui-interacted');
                    } catch (e) {
                        console.error(e);
                    }
                });

                tomSelect.on('item_add', function () {
                    this.setTextboxValue('');
                    this.refreshOptions();
                });
            }
        } catch (e) {
            console.error(e);
        }
    }
}

const onReady = function () {
    BubbleSelect.init();
};

if (document.readyState !== 'loading') {
    onReady();
} else {
    document.addEventListener('DOMContentLoaded', onReady);
}
