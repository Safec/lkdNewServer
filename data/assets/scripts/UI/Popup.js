export default class Popup {
    static alert(message, title = '', button = 'OK') {
        const $prototype = document.querySelector('#popup-alert-prototype');
        if (!$prototype) {
            alert(message);
            return;
        }

        const $alert = $prototype.cloneNode(true);
        $alert.id = 'popup-alert';
        $alert.innerHTML = $alert.innerHTML
            .replace('{{message}}', message)
            .replace('{{title}}', title)
            .replace('{{button}}', button);

        document.body.appendChild($alert);
        $alert.classList.remove('!hidden');
    }

    static confirm(question, callback) {
        const $prototype = document.querySelector('#popup-question-prototype');
        if (!$prototype) {
            if (confirm(question) && typeof callback === 'function') {
                try {
                    callback();
                } catch (e) {
                    console.error(e);
                }

                return;
            }
        }

        const $confirm = $prototype.cloneNode(true);
        $confirm.id = 'popup-question';
        $confirm.innerHTML = $confirm.innerHTML.replace('{{question}}', question);

        const $yes = $confirm.querySelector('.popup-yes');
        if ($yes) {
            $yes.addEventListener('click', function () {
                if (typeof callback === 'function') {
                    try {
                        callback();
                    } catch (e) {
                        console.error(e);
                    }
                }

                $confirm.remove();
            });
        }

        document.body.appendChild($confirm);
        $confirm.classList.remove('!hidden');
    }
}
