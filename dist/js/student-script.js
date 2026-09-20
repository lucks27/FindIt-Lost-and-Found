document.addEventListener("DOMContentLoaded", () => {

    // -----------------------------
    // LOGIN BUTTON → STUDENT WELCOME
    // -----------------------------
    const loginBtn = document.querySelector(".btn-login");
    if (loginBtn) {
        loginBtn.addEventListener("click", () => {
            window.location.href = window.routes.welcome;
        });
    }

    // -----------------------------
    // LOGOUT BUTTON
    // -----------------------------
    const logoutBtn = document.getElementById('logoutBtn');

  if (logoutBtn) {
      logoutBtn.addEventListener('click', function (event) {
          event.preventDefault();

          if (confirm("Are you sure you want to log out?")) {
              window.location.href = window.routes.login;
          }
      });
  }

    // -----------------------------
    // OK BUTTON → NOTIFICATIONS
    // -----------------------------
    const okBtn = document.querySelector(".ok-btn, #okBtn");
    if (okBtn) {
        okBtn.addEventListener("click", () => {
            window.location.href = window.routes.studentNotifications;
        });
    }

    // -----------------------------
    // PROFILE PAGE BUTTONS
    // -----------------------------
    const editProfileBtn = document.getElementById("editProfileBtn");
    const bookingHistoryBtn = document.getElementById("bookingHistoryBtn");
    const accountBtn = document.getElementById("accountBtn");
    const backBtn = document.getElementById("backBtn");

    if (editProfileBtn) {
        editProfileBtn.addEventListener("click", () => {
            window.location.href = window.routes.profileEdit;
        });
    }

    if (bookingHistoryBtn) {
        bookingHistoryBtn.addEventListener("click", () => {
            window.location.href = window.routes.bookingHistory;
        });
    }

    if (accountBtn) {
        accountBtn.addEventListener("click", () => {
            window.location.href = window.routes.profileAccount;
        });
    }

    if (backBtn) {
        backBtn.addEventListener("click", () => {
            window.location.href = window.routes.profile;
        });
    }

    // -----------------------------
    // FACILITY BOOKING AUTO-FILL
    // -----------------------------
    const facilitySelect = document.getElementById("facility-select");
    const facilityImage = document.getElementById("facility-image");

    if (facilitySelect && facilityImage) {
        facilitySelect.addEventListener("change", function () {
            const selectedOption = facilitySelect.selectedOptions[0];

            if (selectedOption) {
                const imageUrl = selectedOption.getAttribute("data-image");
                const facilityName = selectedOption.getAttribute("data-name");

                facilityImage.src = imageUrl;
                facilityImage.alt = facilityName;
            }
        });
    }

    // -----------------------------
    // BOOKING FORM HANDLER
    // -----------------------------
    const bookingForm = document.getElementById("bookingForm");
    if (bookingForm) {
        bookingForm.addEventListener("submit", function () {
            alert("Booking submitted successfully!");
        });
    }

    const downloadBtn = document.getElementById("downloadBtn");
    const content = document.getElementById("confirmationContent");

    if (downloadBtn && content) {
        downloadBtn.addEventListener("click", function () {

            const opt = {
                margin: 10,
                filename: 'booking-confirmation.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().from(content).set(opt).save();
        });
    }

});
