export default class DocumentUploader {
    static onChange($uploader) {
        try {
            const $container = $uploader.parentNode;

            const $file = $container.querySelector('input[type="file"]');
            if ($file.hasAttribute('data-maxsize')) {
                const maxSize = parseInt($file.dataset.maxsize);
                if (maxSize > 0) {
                    const maxSizeMb = Math.round(maxSize / 1024 / 1024);
                    for (const file of $file.files) {
                        if (file.size > maxSize) {
                            $file.value = '';

                            const message = 'Файл нельзя добавить. Размер файла «' + file.name + '» больше допустимых ' + maxSizeMb + ' МБ.';
                            try {
                                App.UI.Popup.alert(message, 'Ошибка');
                            } catch (e) {
                                console.error(e);
                                alert(message);
                            }

                            return;
                        }
                    }
                }
            }


            const $form = $container.closest('form');
            if ($form.hasAttribute('data-maxsize')) {
                const formMaxSize = parseInt($form.dataset.maxsize);
                if (formMaxSize > 0) {
                    const formMaxSizeMb = Math.round(formMaxSize / 1024 / 1024);
                    const formSize = this.getFormFilesSize($form);
                    if (formSize > formMaxSize) {
                        $file.value = '';
                        const message = 'Файл нельзя добавить. Общий размер файлов больше допустимых ' + formMaxSizeMb + ' МБ.';
                        try {
                            App.UI.Popup.alert(message, 'Ошибка');
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
                        filesCount += $input.files.length;
                    }

                    if (filesCount >= formMaxFiles) {
                        $file.value = '';

                        const message = 'Файл нельзя добавить. Достигнуто максимальное количество файлов.';
                        try {
                            App.UI.Popup.alert(message, 'Ошибка');
                        } catch (e) {
                            console.error(e);
                            alert(message);
                        }

                        return;
                    }
                }
            }

            const $hidden = $uploader.querySelector('input[type="hidden"]');
            $hidden.value = '';
            $uploader.classList.add('!hidden');

            const $prototype = document.createElement('div');
            $prototype.innerHTML = $container.dataset.prototype.replaceAll(
                '__name__',
                ++$container.dataset.index
            );

            const $new = $prototype.firstElementChild;
            $container.appendChild($new);

            this.renderTags($container, $new);
        } catch (e) {
            console.error(e);
        }
    }

    static getFormFilesSize($form) {
        try {
            let size = 0;
            const $files = $form.querySelectorAll('input[type="file"]');
            for (const $file of $files) {
                for (const file of $file.files) {
                    size += file.size;
                }
            }

            return size;
        } catch (e) {
            console.error(e);
        }

        return null;
    }

    static renderTags($container, $uploader) {
        const $tags = $uploader.querySelector('.uploader__tags');
        $tags.querySelectorAll('.uploader__tags__tag').forEach($tag => {
            try {
                if (!document.getElementById($tag.dataset.id)) {
                    $tag.remove();
                }
            } catch (e) {
                console.error(e);
            }
        });

        $container.querySelectorAll('input[type="file"]').forEach($input => {
            try {
                const file = $input.files[0] || null;
                if (file) {
                    const $tagPrototype = document.createElement('div');
                    $tagPrototype.innerHTML = $container.dataset.tagPrototype.replaceAll(
                        '__name__',
                        file.name
                    ).replaceAll(
                        '__id__',
                        $input.id
                    );

                    const $tag = $tagPrototype.firstElementChild;
                    $tags.appendChild($tag);
                }
            } catch (e) {
                console.error(e);
            }
        });
    }

    static onDelete($button) {
        try {
            const $uploader = document.getElementById($button.dataset.id).parentNode;
            const $tag = $button.closest('.uploader__tags__tag');
            const $form = $button.closest('form');
            setTimeout(() => {
                $uploader.remove();
                $tag.remove();
                $form.dispatchEvent(new Event('change', {bubbles: true}));
            }, 0);
        } catch (e) {
            console.error(e);
        }
    }
}