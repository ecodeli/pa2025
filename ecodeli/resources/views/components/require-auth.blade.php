@push('scripts')
    <script type="module">
        import { requireAuth } from "/js/access-control.js";
        requireAuth(@json($role));
    </script>
@endpush
