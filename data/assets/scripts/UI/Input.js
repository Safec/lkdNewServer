import Alert from "./Alert.js";

export default class Input {
    static copyToClipboard(input, message = '') {
        try {
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand("copy");
            input.setSelectionRange(0, 0)
            input.blur();
            if (message) {
                Alert.success(message);
            }
        } catch (e) {
            console.error(e);
        }
    }
}