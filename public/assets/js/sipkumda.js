// =====================
// Loading Screen
// =====================

document.addEventListener("DOMContentLoaded", function () {
    // Simulasi waktu loading
    setTimeout(function () {
        // Cek apakah elemen dengan ID "loading-screen" ada
        const loadingScreen = document.getElementById("loading-screen");
        if (loadingScreen) {
            // Menambahkan kelas untuk memicu efek fade-out
            loadingScreen.classList.add("hidden");

            // Menunggu transisi selesai sebelum menyembunyikan elemen
            setTimeout(function () {
                loadingScreen.style.display = "none";
                // Menampilkan konten utama
                const mainContent = document.getElementById("main-content");
                if (mainContent) {
                    mainContent.classList.remove("d-none");
                }
            }, 500); // Sesuaikan dengan durasi transisi fade-out
        }
    }, 2000); // Sesuaikan penundaan sesuai kebutuhan
});

// =====================
// SideNav Setting
// =====================

document.addEventListener("DOMContentLoaded", function () {
    const sidenav = document.querySelector(".sidenav");
    const sidenavToggler = document.querySelector("#sidenav-toggler");
    const togglerInner = sidenavToggler.querySelector(".sidenav-toggler-inner");
    const mainContent = document.querySelector(".main-content");

    // Fungsi untuk menyesuaikan tampilan berdasarkan ukuran layar saat halaman dimuat
    function adjustSidebar() {
        if (window.innerWidth >= 1440) {
            sidenav.classList.add("minimized");
            sidenav.classList.remove("show");
            document.body.classList.remove(
                "g-sidenav-hidden",
                "nav-open",
                "g-sidenav-show"
            );
            document.body.classList.add("g-sidenav-pinned");
            mainContent.style.marginLeft = "80px";
        } else if (window.innerWidth >= 1200 && window.innerWidth < 1440) {
            sidenav.classList.add("minimized");
            sidenav.classList.remove("show");
            document.body.classList.remove(
                "g-sidenav-hidden",
                "nav-open",
                "g-sidenav-show"
            );
            document.body.classList.add("g-sidenav-pinned");
            mainContent.style.marginLeft = "80px";
        } else {
            sidenav.classList.remove("minimized");
            sidenav.classList.remove("show");
            document.body.classList.remove("g-sidenav-pinned");
            document.body.classList.add("g-sidenav-hidden");
            mainContent.style.marginLeft = "0";
        }
    }

    // Panggil adjustSidebar saat halaman dimuat
    adjustSidebar();

    // Event listener untuk toggle sidenav pada semua ukuran layar
    sidenavToggler.addEventListener("click", function () {
        togglerInner.classList.toggle("toggled"); // Toggle class .toggled

        // Handle ukuran layar besar dan medium
        if (window.innerWidth >= 1200) {
            sidenav.classList.toggle("minimized");
            document.body.classList.toggle("g-sidenav-pinned");
            document.body.classList.toggle("g-sidenav-hidden");

            if (sidenav.classList.contains("minimized")) {
                mainContent.style.marginLeft = "80px";
            } else {
                mainContent.style.marginLeft = "250px";
            }
        } else {
            // Handle untuk ukuran layar kecil (di bawah 1200px)
            sidenav.classList.toggle("show");
            document.body.classList.toggle("g-sidenav-show");
            document.body.classList.toggle("g-sidenav-hidden");
            mainContent.style.marginLeft = "0"; // Sidenav disembunyikan di mobile
        }
    });

    // Event listener untuk menutup sidenav saat klik di luar sidenav (layar kecil)
    document.addEventListener("click", function (e) {
        if (window.innerWidth < 1200) {
            if (
                !sidenav.contains(e.target) &&
                !sidenavToggler.contains(e.target)
            ) {
                // Klik di luar sidenav, tutup sidenav
                sidenav.classList.remove("show");
                document.body.classList.add("g-sidenav-hidden");
                document.body.classList.remove("g-sidenav-show", "nav-open");
                mainContent.style.marginLeft = "0";
            }
        }
    });

    // Reset layout saat ukuran layar berubah
    window.addEventListener("resize", function () {
        adjustSidebar();
    });
});
