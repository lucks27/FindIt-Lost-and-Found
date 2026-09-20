document.addEventListener('DOMContentLoaded', () => {
  const bookBtn = document.getElementById('bookBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const popup = document.getElementById('confirmationPopup');
  const yesBtn = document.getElementById('confirmYes');
  const noBtn = document.getElementById('confirmNo');
  const facilityInput = document.getElementById('facility');

  // Auto-fill facility from localStorage
  const selectedFacility = localStorage.getItem('selectedFacility');
  if (selectedFacility) {
    facilityInput.value = selectedFacility;
    localStorage.removeItem('selectedFacility');
  }

  // BOOK button click
  bookBtn.addEventListener('click', () => {
    const studentId = document.getElementById('studentId').value.trim();
    const studentName = document.getElementById('studentName').value.trim();
    const facility = facilityInput.value.trim();
    const date = document.getElementById('date').value;
    const timeIn = document.getElementById('timeIn').value;
    const timeOut = document.getElementById('timeOut').value;

    if (!studentId || !studentName || !facility || !date || !timeIn || !timeOut) {
      alert('Please fill in all required fields.');
      return;
    }

    if (timeOut <= timeIn) {
      alert('End time must be after start time.');
      return;
    }

    popup.style.display = 'flex';
    popup.setAttribute('aria-hidden', 'false');

    // Save temporary booking data
    popup.dataset.booking = JSON.stringify({ studentId, studentName, facility, date, timeIn, timeOut });
  });

  // Confirm booking
  yesBtn.addEventListener('click', () => {
    const booking = JSON.parse(popup.dataset.booking);
    const bookings = JSON.parse(localStorage.getItem('bookings') || '[]');
    bookings.push(booking);
    localStorage.setItem('bookings', JSON.stringify(bookings));

    popup.style.display = 'none';
    setTimeout(() => {
      window.location.href = 'booking-history.html';
    }, 200);
  });

  // Cancel booking
  noBtn.addEventListener('click', () => {
    popup.style.display = 'none';
    window.location.href = 'facility.html'; // back to previous page
  });

  cancelBtn.addEventListener('click', () => {
    window.location.href = 'facility.html';
  });
});
