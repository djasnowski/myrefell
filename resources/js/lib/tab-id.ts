/**
 * Tab ID management for detecting multi-tab abuse.
 *
 * Each browser tab gets a unique ID that persists for the tab's lifetime
 * (survives page navigations, but cleared when tab is closed).
 */

const TAB_ID_KEY = "myrefell_tab_id";

/**
 * Get or create a unique tab ID for this browser tab.
 * Uses sessionStorage so it persists across navigations but not across tabs.
 */
export function getTabId(): string {
    let tabId = sessionStorage.getItem(TAB_ID_KEY);

    if (!tabId) {
        tabId = crypto.randomUUID();
        sessionStorage.setItem(TAB_ID_KEY, tabId);
    }

    return tabId;
}

/**
 * Initialize the tab ID system by setting up Axios interceptors.
 * This adds the X-Tab-ID header to all outgoing requests.
 */
export function initializeTabTracking(): void {
    const tabId = getTabId();

    // Add tab ID to all fetch requests by patching the global fetch
    const originalFetch = window.fetch;
    window.fetch = function (input: RequestInfo | URL, init?: RequestInit): Promise<Response> {
        const headers = new Headers(init?.headers);
        headers.set("X-Tab-ID", tabId);

        return originalFetch(input, {
            ...init,
            headers,
        });
    };

    // Also patch XMLHttpRequest for any non-fetch requests
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (
        method: string,
        url: string | URL,
        async: boolean = true,
        username?: string | null,
        password?: string | null,
    ): void {
        // Store the open call to be used later
        (this as XMLHttpRequest & { _tabIdSet?: boolean })._tabIdSet = false;
        return originalXHROpen.call(this, method, url, async, username, password);
    };

    XMLHttpRequest.prototype.send = function (
        body?: Document | XMLHttpRequestBodyInit | null,
    ): void {
        if (!(this as XMLHttpRequest & { _tabIdSet?: boolean })._tabIdSet) {
            this.setRequestHeader("X-Tab-ID", tabId);
            (this as XMLHttpRequest & { _tabIdSet?: boolean })._tabIdSet = true;
        }
        return originalXHRSend.call(this, body);
    };
}
