(() => {
    const storageKey = 'hyperpos.layout.safeBackStack';
    const maxEntries = 30;

    const normalizeUrl = (value) => {
        try {
            const url = new URL(value, window.location.origin);
            url.hash = '';
            return url.href;
        } catch (error) {
            return '';
        }
    };

    const currentUrl = () => normalizeUrl(window.location.href);

    const isSameOrigin = (value) => {
        try {
            return new URL(value, window.location.origin).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    };

    const isFormLikeUrl = (value) => {
        try {
            const path = new URL(value, window.location.origin).pathname.toLowerCase();

            return /\/(create|edit|form)(\/|$)/.test(path);
        } catch (error) {
            return true;
        }
    };

    const readStack = () => {
        try {
            const parsed = JSON.parse(window.sessionStorage.getItem(storageKey) || '[]');
            return Array.isArray(parsed) ? parsed.filter((url) => typeof url === 'string') : [];
        } catch (error) {
            return [];
        }
    };

    const writeStack = (stack) => {
        const normalized = stack
            .map((url) => normalizeUrl(url))
            .filter((url) => url !== '' && isSameOrigin(url))
            .slice(-maxEntries);

        window.sessionStorage.setItem(storageKey, JSON.stringify(normalized));
    };

    const rememberCurrentPage = () => {
        const current = currentUrl();

        if (!current) {
            return;
        }

        const stack = readStack().filter((url) => normalizeUrl(url) !== current);
        stack.push(current);
        writeStack(stack);
    };

    const findSafeBackTarget = (fallbackUrl) => {
        const current = currentUrl();
        const fallback = normalizeUrl(fallbackUrl);
        const stack = readStack()
            .map((url) => normalizeUrl(url))
            .filter((url) => url !== '' && isSameOrigin(url));

        for (let index = stack.length - 1; index >= 0; index -= 1) {
            const candidate = stack[index];

            if (candidate === current) {
                continue;
            }

            if (isFormLikeUrl(candidate)) {
                continue;
            }

            return candidate;
        }

        if (
            fallback &&
            fallback !== current &&
            isSameOrigin(fallback) &&
            !isFormLikeUrl(fallback)
        ) {
            return fallback;
        }

        return '';
    };

    rememberCurrentPage();

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-layout-smart-back]');

        if (!trigger) {
            return;
        }

        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const target = findSafeBackTarget(trigger.getAttribute('href') || '');

        if (!target) {
            return;
        }

        event.preventDefault();

        const current = currentUrl();
        const prunedStack = readStack()
            .map((url) => normalizeUrl(url))
            .filter((url) => url !== '' && url !== current && url !== target);

        prunedStack.push(target);
        writeStack(prunedStack);

        window.location.assign(target);
    });
})();
