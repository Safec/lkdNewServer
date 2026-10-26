const onReady = function () {
    const $timer = document.querySelector('#auth-sms-timer[data-timer]');
    const $link = document.querySelector('#auth-sms-link');

    if ($timer) {
        const $display = $timer.querySelector('[data-timer-display]');
        let timer = parseInt($timer.getAttribute('data-timer'));

        if (timer > 0) {
            $timer.classList.remove('hidden');
            if ($link) {
                $link.classList.add('hidden');
            }

            const interval = setInterval(function () {
                timer--;

                if (timer <= 0) {
                    clearInterval(interval);
                    $timer.classList.add('hidden');
                    if ($link) {
                        $link.classList.remove('hidden');
                    }
                }

                if ($display) {
                    $display.innerText = timer;
                }
            }, 1000);
        } else {
            $timer.classList.add('hidden');
            if ($link) {
                $link.classList.remove('hidden');
            }
        }
    }
}

if (document.readyState !== 'loading') {
    onReady();
} else {
    document.addEventListener('DOMContentLoaded', onReady);
}