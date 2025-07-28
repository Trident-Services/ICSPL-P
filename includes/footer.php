<!-- includes/footer.php -->
<script>
    // Search by name or email
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll("table tbody tr");

            rows.forEach(row => {
                const name = row.cells[1]?.textContent.toLowerCase() || "";
                const email = row.cells[3]?.textContent.toLowerCase() || "";
                row.style.display = (name.includes(filter) || email.includes(filter)) ? "" : "none";
            });
        });
    }

    // Filter login logs by date
    const logDatePicker = document.getElementById("logDatePicker");
    if (logDatePicker) {
        logDatePicker.addEventListener("change", function () {
            const selectedDate = this.value;
            const rows = document.querySelectorAll("#logTable tbody tr");

            rows.forEach(row => {
                const loginTime = row.cells[2]?.textContent || "";
                const logDate = loginTime.split(" ")[0];
                row.style.display = (!selectedDate || logDate === selectedDate) ? "" : "none";
            });
        });
    }
</script>

</body>
</html>
