# MyRefell Development Notes

## CSRF Token / 419 Page Expired Fix

When using `router.post()`, `router.put()`, or `router.delete()` in Inertia.js React components, always add `router.reload()` in the `onSuccess` callback to refresh the CSRF token. Without this, subsequent requests will fail with "419 Page Expired".

```tsx
router.post(
    '/some/endpoint',
    { data: value },
    {
        preserveScroll: true,
        onSuccess: () => {
            router.reload();
        },
        onFinish: () => {
            setLoading(false);
        },
    }
);
```

This pattern must be applied to ALL `router.post()`, `router.put()`, and `router.delete()` calls across the application.

**Note:** If the onSuccess already calls `router.visit()` (navigating to another page), `router.reload()` is not needed since the new page will have a fresh CSRF token.

## Backend Inertia Responses

When creating POST/PUT/DELETE endpoints that are called via Inertia's `router.post()`, always return a redirect response (using `back()` or `redirect()`) instead of a JSON response. Returning JSON will cause an "All Inertia requests must receive a valid Inertia response" error.

```php
// Correct - redirect back with flash message
return back()->with('success', 'Action completed!');

// Incorrect - returns JSON
return response()->json(['success' => true]);
```
