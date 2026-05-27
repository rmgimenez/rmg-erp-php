<!-- Toast System -->
<script src="assets/js/toast.js"></script>
<script>
    if (window._pendingToast) {
        exibirToast(window._pendingToast.message, window._pendingToast.type);
        delete window._pendingToast;
    }
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
