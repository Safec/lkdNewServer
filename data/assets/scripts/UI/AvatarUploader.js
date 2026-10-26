export default class AvatarUploader {
    static onChange(input) {
        try {
            const file = input.files[0];
            if (file) {
                if (input.hasAttribute('data-maxsize')) {
                    const maxSize = parseInt(input.getAttribute('data-maxsize'));
                    if (maxSize > 0) {
                        const maxSizeMb = Math.round(maxSize / 1024 / 1024);
                        if (file.size > maxSize) {
                            input.value = '';

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

                const $label = input.parentNode.parentNode.querySelector('label');
                const $delete = $label.parentNode.querySelector('.profile__file-uploader__delete');

                const reader = new FileReader();
                reader.addEventListener('load', function () {
                    $label.querySelector('span').classList.add('!hidden');
                    $label.querySelectorAll('img').forEach(function (img) {
                        img.remove();
                    });

                    $delete.classList.remove('!hidden');

                    const img = document.createElement('img');
                    img.src = String(this.result);
                    $label.appendChild(img);
                });

                reader.readAsDataURL(file);
            }
        } catch (e) {
            console.error(e);
        }
    }

    static onDelete(button) {
        try {
            const $input = button.parentNode.querySelector('input[type="file"]');
            if ($input) {
                $input.value = '';
            }

            const $avatar = button.parentNode.querySelector('input[type="hidden"]');
            if ($avatar) {
                $avatar.value = '';
            }

            const $label = button.parentNode.querySelector('label');
            const $img = $label.querySelector('img');

            $img.remove();
            $label.querySelector('span').classList.remove('!hidden');
            button.classList.add('!hidden');
        } catch (e) {
            console.error(e);
        }
    }
}
