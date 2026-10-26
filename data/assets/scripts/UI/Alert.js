export default class Alert {
    message = '';
    type = 'success';

    constructor(message, type = 'success') {
        this.message = message;
        this.type = type;
    }

    render(container = document.querySelector('#ui-alerts')) {
        const alert = document.createElement('div');
        alert.classList.add('alert', `alert--${this.type}`, 'fade');
        alert.textContent = this.message;

        container.appendChild(alert);
        setTimeout(() => {
            alert.classList.remove('fade');
        }, 1)

        setTimeout(() => {
            alert.classList.add('fade');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    }

    static success(message) {
        (new Alert(message, 'success')).render();
    }

    static warning(message) {
        (new Alert(message, 'warning')).render();
    }

    static error(message) {
        (new Alert(message, 'error')).render();
    }

    static onReady() {
        for (const alert of window.__alerts__.success || []) {
            this.success(alert);
        }

        for (const alert of window.__alerts__.warning || []) {
            this.warning(alert);
        }

        for (const alert of window.__alerts__.error || []) {
            this.error(alert);
        }
    }
}

if (document.readyState !== 'loading') {
    Alert.onReady();
} else {
    document.addEventListener('DOMContentLoaded', Alert.onReady);
}