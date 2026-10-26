import IMask from "imask";

const initMasks = function () {
    const inputs = document.querySelectorAll('input[data-mask]');
    inputs.forEach(function (input) {
        const mask = input.getAttribute('data-mask');
        // noinspection JSCheckFunctionSignatures
        IMask(input, {mask: mask});
    });
};

const initSubmitOnInput = function () {
    const inputs = document.querySelectorAll('input[data-mask-submit]');
    inputs.forEach(function (input) {
        const mask = input.getAttribute('data-mask');
        if (typeof mask === 'string' && mask.length > 0) {
            let masked = IMask.createMask({mask: mask});
            input.addEventListener('input', function () {
                masked.resolve(input.value);
                if (masked.isComplete) {
                    input.form.submit();
                }
            });
        }
    });
};

const initFocusOnLoad = function () {
    const input = document.querySelector('input[data-focus-onload]');
    if (input) {
        input.focus();
    }
};

const onReady = function () {
    initMasks();
    initSubmitOnInput();
    initFocusOnLoad();
};

if (document.readyState !== 'loading') {
    onReady();
} else {
    document.addEventListener('DOMContentLoaded', onReady)
}