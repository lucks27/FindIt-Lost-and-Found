document.addEventListener('DOMContentLoaded', function () {
    const loginBtn = document.querySelector('.btn-login');
    const registerBtn = document.querySelector('.register-btn');
    const manageBtn = document.querySelector('.manage-btn');
    const logoutBtn = document.querySelector('.btn-logout');
    const availableBtn = document.querySelector('.btn-available');
    const unavailableBtn = document.querySelector('.btn-unavailable');
    const maintenanceBtn = document.querySelector('.btn-maintenance');
    const confirmBtns = document.querySelectorAll('.confirm');
    const cancelBtns = document.querySelectorAll('.cancel');
    const openProfilePopup = document.getElementById("openProfilePopup");
    const profileOverlay = document.getElementById("profileOverlay");
    const closeProfilePopup = document.getElementById("closeProfilePopup");
    const chooseFileBtn = document.getElementById("chooseFileBtn");
    const profileInput = document.getElementById("profileInput");
    const previewImage = document.getElementById("previewImage");
    const profilePic = document.getElementById("profilePic");
    const saveProfileBtn = document.getElementById("saveProfileBtn");
    const personalBtn = document.getElementById('personalBtn');
    const accountBtn = document.getElementById('accountBtn');
    const personalOverlay = document.getElementById('personalOverlay');
    const accountOverlay = document.getElementById('accountOverlay');

    function disableRowButtons(row) {
        row.querySelectorAll('.confirm, .cancel').forEach(button => button.disabled = true);
    }

    if (availableBtn) {
        availableBtn.addEventListener('click', function (event) {
            event.preventDefault();
            const confirmUpdate = confirm("Are you sure you want update the status of this facility?");
            if (confirmUpdate) {
                alert("Facility status has been successfully updated!");
                window.location.href = routes.dashboard;
            }
        });
    }

    if (unavailableBtn) {
        unavailableBtn.addEventListener('click', function (event) {
            event.preventDefault();
            const confirmUpdate = confirm("Are you sure you want update the status of this facility?");
            if (confirmUpdate) {
                alert("Facility status has been successfully updated!");
                window.location.href = routes.dashboard;
            }
        });
    }

    if (maintenanceBtn) {
        maintenanceBtn.addEventListener('click', function (event) {
            event.preventDefault();
            const confirmUpdate = confirm("Are you sure you want update the status of this facility?");
            if (confirmUpdate) {
                alert("Facility status has been successfully updated!");
                window.location.href = routes.dashboard;
            }
        });
    }
    if (registerBtn) {
        registerBtn.addEventListener('click', function () {
            window.location.href = 'login.html';
        });
    }

    openProfilePopup.addEventListener("click", () => {
        profileOverlay.style.display = "flex";
    });

    // CLOSE POPUP
    closeProfilePopup.addEventListener("click", () => {
        profileOverlay.style.display = "none";
    });

    // OPEN FILE EXPLORER
    chooseFileBtn.addEventListener("click", () => {
        profileInput.click();
    });

    // PREVIEW SELECTED IMAGE
    profileInput.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = e => {
            previewImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    saveProfileBtn.addEventListener("click", () => {
        profilePic.src = previewImage.src;
        profileOverlay.style.display = "none";
    });

    document.getElementById('closePersonal').addEventListener('click', () => personalOverlay.style.display = 'none');
    document.getElementById('closeAccount').addEventListener('click', () => accountOverlay.style.display = 'none');

    // Open overlays
    personalBtn.addEventListener('click', () => {
        personalOverlay.style.display = 'flex';
        document.getElementById('personalName').focus();
    });

    accountBtn.addEventListener('click', () => {
        accountOverlay.style.display = 'flex';
        document.getElementById('oldPassword').focus();
    });


    bookingBtn.addEventListener('click', () => {
        window.location.href = '/bookinghistory'; // your booking history route
    });

    logoutBtn.addEventListener('click', () => {
        window.location.href = '/login'; // your login route
    });
});
