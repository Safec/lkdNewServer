export default class Form {
    static addFormToCollection($source, $target) {
        const $prototype = $source.closest('[data-prototype]');
        if (!$prototype) {
            return;
        }

        const prototype = $prototype.dataset.prototype;
        const index = $prototype.dataset.index;

        const $item = document.createElement('div');
        $item.innerHTML = prototype.replace(/__name__/g, index);

        $target.appendChild($item.firstElementChild);
        $prototype.dataset.index++;
    }
}