document.addEventListener('DOMContentLoaded', () => {

  /* =============================
     LOGIN PAGE
  ============================== */
  const loginForm = document.getElementById('loginForm');
  const loginBtn = document.getElementById('loginBtn');

  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();

      if (email === "" || password === "") {
        alert("Please fill in all fields.");
      } else {
        alert("Login successful! (Demo only)");
        window.location.href = "landing-page.html"; // ✅ Redirect to landing page
      }
    });
  }

  if (loginBtn) {
    loginBtn.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = "landing-page.html";
    });
  }

  /* =============================
     LANDING PAGE
  ============================== */
  const exploreBtn = document.getElementById('exploreBtn');
  if (exploreBtn) {
    exploreBtn.addEventListener('click', (e) => {
      e.preventDefault();
      exploreBtn.innerText = "Loading...";
      setTimeout(() => {
        window.location.href = "homepage.html"; // ✅ Go to homepage
      }, 1000);
    });
  }

  /* =============================
     FACILITY PAGE
  ============================== */
  const params = new URLSearchParams(window.location.search);
  const facilityKey = params.get("facility");

  if (facilityKey) {
    const facilities = {
      lrc: {
        name: "Learners’ Resource Center",
        type: "Resource Center",
        location: "Tapad Gym",
        availability: "Available",
        time: "8:00 A.M - 12:00 N.N<br>1:00 P.M - 5:00 P.M",
        image: "images/lrc.jpg"
      },
      academic: {
        name: "Academic Hall",
        type: "Event Hall",
        location: "Main Building",
        availability: "Available",
        time: "8:00 A.M - 6:00 P.M",
        image: "images/academic-hall.jpg"
      },
      innovation: {
        name: "Innovation Room",
        type: "Study Area",
        location: "IT Building, 2nd Floor",
        availability: "Occupied",
        time: "8:00 A.M - 5:00 P.M",
        image: "images/innovation-room.jpg"
      }
    };

    const facility = facilities[facilityKey];

    if (facility) {
      document.getElementById("facilityName").textContent = facility.name;
      document.getElementById("facilityType").textContent = facility.type;
      document.getElementById("facilityLocation").textContent = facility.location;
      document.getElementById("facilityAvailability").textContent = facility.availability;
      document.getElementById("facilityTime").innerHTML = facility.time;
      document.getElementById("facilityImage").src = facility.image;

      // Style availability dynamically
      const statusEl = document.getElementById("facilityAvailability");
      if (facility.availability.toLowerCase() === "available") {
        statusEl.classList.add("available");
      } else {
        statusEl.classList.add("unavailable");
      }

      // Reserve button logic
      const reserveBtn = document.querySelector(".reserve-btn");
      if (reserveBtn) {
        reserveBtn.addEventListener("click", () => {
          window.location.href = `booking-form.html?facility=${facilityKey}`;
        });
      }
    } else {
      const container = document.querySelector(".facility-details");
      if (container) {
        container.innerHTML = "<p>Facility not found.</p>";
      }
    }
  }
});
