document.addEventListener("DOMContentLoaded", () => {
  const viewMoreLink = document.getElementById("viewMoreLink");
  const confirmationBox = document.getElementById("confirmationBox");
  const downloadBtn = document.getElementById("downloadBtn");

  // Show confirmation section when "View more" clicked
  viewMoreLink.addEventListener("click", (e) => {
    e.preventDefault();
    confirmationBox.classList.remove("hidden");
    window.scrollTo({ top: confirmationBox.offsetTop - 50, behavior: "smooth" });
  });

  // Download confirmation slip (simple placeholder)
  downloadBtn.addEventListener("click", () => {
    const blob = new Blob(
      ["SNSU Booking Confirmation\n\nYour booking has been approved.\nPlease present this slip to the facility admin."],
      { type: "text/plain" }
    );
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "BookingConfirmation.txt";
    link.click();
  });
});
