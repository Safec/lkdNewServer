import Popup from "./Popup.js";

export default class ProjectUploader {
    static onChangeFile($uploader) {
        try {
            const self = this;

            const $file = $uploader.querySelector('input[type="file"]');
            if (!$file) {
                return;
            }

            const file = $file.files[0];
            if (!file) {
                return;
            }

            if ($file.hasAttribute('data-maxsize')) {
                const maxSize = parseInt($file.getAttribute('data-maxsize'));
                if (maxSize > 0) {
                    const maxSizeMb = Math.round(maxSize / 1024 / 1024);
                    if (file.size > maxSize) {
                        $file.value = '';

                        const message = 'Файл нельзя добавить. Размер файла «' + file.name + '» больше допустимых ' + maxSizeMb + ' МБ.';
                        try {
                            Popup.alert(message, 'Ошибка');
                        } catch (e) {
                            console.error(e);
                            alert(message);
                        }

                        return;
                    }
                }
            }

            const $form = $file.closest('form');
            if ($form.hasAttribute('data-maxsize')) {
                const formMaxSize = parseInt($form.dataset.maxsize);
                if (formMaxSize > 0) {
                    const formMaxSizeMb = Math.round(formMaxSize / 1024 / 1024);

                    let formSize = 0;
                    for (const $file of $form.querySelectorAll('input[type="file"]')) {
                        for (const file of $file.files) {
                            formSize += file.size;
                        }
                    }

                    if (formSize > formMaxSize) {
                        $file.value = '';

                        const message = 'Файл нельзя добавить. Общий размер файлов больше допустимых ' + formMaxSizeMb + ' МБ.';
                        try {
                            Popup.alert(message, 'Ошибка');
                        } catch (e) {
                            console.error(e);
                            alert(message);
                        }

                        return;
                    }
                }
            }

            if ($form.hasAttribute('data-maxfiles')) {
                const formMaxFiles = parseInt($form.dataset.maxfiles);
                if (formMaxFiles > 0) {
                    let filesCount = 0;
                    const $inputs = $form.querySelectorAll('input[type="file"]');
                    for (const $input of $inputs) {
                        filesCount += Math.max(1, $input.files.length);
                    }

                    if (filesCount >= formMaxFiles) {
                        $file.value = '';

                        const message = 'Файл нельзя добавить. Достигнуто максимальное количество файлов.';
                        try {
                            Popup.alert(message, 'Ошибка');
                        } catch (e) {
                            console.error(e);
                            alert(message);
                        }

                        return;
                    }
                }
            }

            const reader = new FileReader();
            reader.addEventListener('load', function () {
                const $img = document.createElement('img');
                $img.src = String(this.result);

                $uploader.appendChild($img);

                const $hidden = $uploader.querySelector('input[type="hidden"]');
                if ($hidden) {
                    $hidden.value = '';
                }

                const $deleteButton = $uploader.querySelector('.ui-delete');
                if ($deleteButton) {
                    $deleteButton.classList.remove('!hidden');
                }

                const $coverButton = $uploader.querySelector('.ui-cover');
                if ($coverButton) {
                    $coverButton.classList.remove('!hidden');
                }

                $uploader.classList.add('!border-none');
                $uploader.classList.remove('empty');

                if (!$uploader.parentNode.querySelector('.empty')) {
                    const $prototype = document.createElement('div');
                    $prototype.innerHTML = $uploader.parentNode.dataset.prototype.replaceAll(
                        '__name__',
                        ++$uploader.parentNode.dataset.index
                    );

                    const $new = $prototype.querySelector('.uploader');
                    self.cleanFileUploader($new);
                    $uploader.parentNode.appendChild($new);
                }
            });

            reader.readAsDataURL(file);
        } catch (e) {
            console.error(e);
        }
    }

    static cleanFileUploader($uploader) {
        const $file = $uploader.querySelector('input[type="file"]');
        if ($file) {
            $file.value = '';
        }

        const $hidden = $uploader.querySelector('input[type="hidden"]');
        if ($hidden) {
            $hidden.value = '';
        }

        $uploader.querySelectorAll('img').forEach(function ($img) {
            $img.remove();
        });

        const $deleteButton = $uploader.querySelector('.ui-delete');
        if ($deleteButton) {
            $deleteButton.classList.add('!hidden');
        }

        const $coverButton = $uploader.querySelector('.ui-cover');
        if ($coverButton) {
            $coverButton.removeAttribute('checked');
            $coverButton.classList.add('!hidden');
        }

        $uploader.classList.remove('!border-none');
        $uploader.classList.add('empty');
    }

    static onDeleteFile($uploader) {
        Popup.confirm('Вы уверены, что хотите удалить фото?', function () {
            $uploader.closest('form').setAttribute('data-changed', '1');
            $uploader.remove();
        });
    }

    static onAddProduct($container) {
        const $prototype = document.createElement('div');
        $prototype.innerHTML = $container.dataset.prototype.replaceAll(
            '__name__',
            ++$container.dataset.index
        );

        const $new = $prototype.querySelector('.product');
        $container.appendChild($new);

        $container.closest('form').setAttribute('data-changed', '1');
    }

    static onDeleteProduct($product) {
        $product.closest('form').setAttribute('data-changed', '1');
        $product.remove();
    }

    static initConfirmCancel() {
        const $button = document.querySelector('.ui-confirm-cancel');
        if (!$button) {
            return;
        }

        const $form = $button.closest('form');
        if (!$form) {
            return;
        }

        $form.querySelectorAll('input, textarea, select').forEach(function ($input) {
            $input.addEventListener('change', function () {
                $form.setAttribute('data-changed', '1');
            });
        });

        $button.addEventListener('click', function (e) {
            if ($form.hasAttribute('data-changed')) {
                e.preventDefault();

                Popup.confirm('Вы уверены, что хотите отменить изменения?', function () {
                    $form.removeAttribute('data-changed');
                    $button.click();
                });
            }
        });
    }
}

const onReady = function () {
    ProjectUploader.initConfirmCancel();
};

if (document.readyState !== 'loading') {
    onReady();
} else {
    document.addEventListener('DOMContentLoaded', onReady)
}