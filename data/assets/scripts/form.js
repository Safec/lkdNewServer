import IMask from "imask";

const initSelect = function () {
    document.querySelectorAll('.symfony-form select').forEach(function (select) {
        const onChange = function () {
            if (select.value === '') {
                select.setAttribute('data-empty', '');
            } else {
                select.removeAttribute('data-empty');
            }
        };

        onChange();
        select.addEventListener('change', onChange);
    });
};

const getInputJsValidationError = function ($input) {
    if ($input.value === '' || ($input.type === 'checkbox' && !$input.checked)) {
        return 'Это обязательное поле.';
    }

    let mask = $input.getAttribute('data-mask');
    if (typeof mask === 'string' && mask.length > 0) {
        let masked = IMask.createMask({mask: mask});
        masked.resolve($input.value);
        if (!masked.isComplete) {
            return 'Неверный формат.';
        }
    }

    return '';
};

const initDisableSubmitWhileFormRequired = function () {
    document.querySelectorAll('[data-form-required]').forEach(function ($submit) {
        try {
            const $form = $submit.closest('form');

            const validate = function () {
                let valid = true;
                $form.querySelectorAll('input[required], textarea[required], select[required]').forEach(function ($input) {
                    let error = getInputJsValidationError($input)
                    if (error) {
                        valid = false;
                    }
                });

                $form.querySelectorAll('.ui-document-uploader.required').forEach(function ($uploader) {
                    let notEmpty = 0;
                    $uploader.querySelectorAll('input').forEach(function ($input) {
                        if ($input.value) {
                            notEmpty++;
                        }
                    });

                    if (notEmpty < 1) {
                        valid = false;
                    }
                });

                if (valid) {
                    $submit.removeAttribute('disabled');
                } else {
                    $submit.setAttribute('disabled', 'disabled');
                }
            };

            $form.querySelectorAll('input[required], textarea[required], select[required]').forEach(function ($input) {
                $input.addEventListener('input', validate);
                $input.addEventListener('change', validate);
            });

            $form.addEventListener('change', validate);

            validate();
        } catch (e) {
            console.error(e);
        }
    });
};

const initShowErrorOnBlurRequired = function () {
    document.querySelectorAll('[data-js-validate] input[required], textarea[required], select[required]').forEach(function ($input) {
        const showError = function ($input, error = 'Ошибка!!!') {
            $input.classList.add('error');
            const $error= $input.parentNode.querySelector('.js-error') || document.createElement('div');
            $error.classList.add('js-error');
            $error.classList.add('error');
            $error.innerHTML = error;
            $input.parentNode.appendChild($error);
        };

        const hideError = function ($input) {
            $input.classList.remove('error');
            const $error = $input.parentNode.querySelector('.js-error');
            if ($error) {
                $error.remove();
            }
        };

        $input.addEventListener('blur', function () {
            let error = getInputJsValidationError(this);
            if (error) {
                showError(this, error);
                this.setAttribute('data-js-validate-on-input', '');
            } else {
                hideError(this);
            }
        });

        $input.addEventListener('input', function () {
            if (this.hasAttribute('data-js-validate-on-input')) {
                let error = getInputJsValidationError(this);
                if (error) {
                    showError(this, error);
                } else {
                    hideError(this);
                }
            }
        });
    });
}

const onReady = function () {
    initSelect();
    initDisableSubmitWhileFormRequired();
    initShowErrorOnBlurRequired();
};

if (document.readyState !== 'loading') {
    onReady();
} else {
    document.addEventListener('DOMContentLoaded', onReady)
}