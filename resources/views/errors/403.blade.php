{{-- A policy may say why (the record waits for the screen); otherwise, the general reason. --}}
<x-error-page
    code="403"
    :title="__('Forbidden')"
    :message="__($exception->getMessage() ?: 'This action is unauthorized.')"
/>
