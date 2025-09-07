document.addEventListener("DOMContentLoaded", () => {
  const authBtns = document.querySelectorAll(".auth-button-modal");
  const closeBtns = document.querySelectorAll(".modal .close");
  const rememberMeButton = document.getElementById('remember-me');
  const submitButtons = document.querySelectorAll(".modal .auth-button");
  let isSubmitting = false;

  // Open modal for login/register
  authBtns.forEach(button => {
    button.addEventListener("click", () => {
      const buttonType = button.getAttribute('data-type');
      let modal;
      if (buttonType === 'login') {
        modal = document.getElementById('loginModal');
      } else if (buttonType === 'register') {
        modal = document.getElementById('registerModal');
      }

      if (modal) {
        const form = modal.querySelector('form');
        if (form) form.reset();
        const messageBox = modal.querySelector('.error-messagebox');
        if (messageBox) messageBox.innerHTML = "&nbsp;";
        modal.querySelectorAll("input").forEach(i => i.classList.remove("error"));
        modal.style.display = "flex";
      }
    });
  });

  // Close modals
  closeBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      btn.closest(".modal").style.display = "none";
    });
  });

  window.addEventListener("click", (event) => {
    // This will only close login/register modals. Profile modals are handled by render.js
    if (event.target.matches('#loginModal, #registerModal')) {
      event.target.style.display = "none";
    }
  });

  // Remember me checkbox
  if (rememberMeButton) {
    rememberMeButton.addEventListener('click', function() {
      this.value = this.checked ? 1 : 0;
    });
  }

  // Handle form submission for login/register
  submitButtons.forEach(button => {
    button.addEventListener('click', async () => {
      if (isSubmitting) return;

      const form = button.closest('form');
      const modal = button.closest('.modal');
      let action = '';

      // Determine action based on which modal we are in
      if (modal.id === 'loginModal') action = 'login';
      if (modal.id === 'registerModal') action = 'register';
      
      // If we are not in a login or register modal, do nothing.
      if (!action) return;

      isSubmitting = true;
      const messageBox = form.querySelector('.error-messagebox');
      if (messageBox) messageBox.innerHTML = "&nbsp;";
      form.querySelectorAll("input").forEach(i => i.classList.remove("error"));

      const data = new FormData(form);
      data.append("action", action);

      try {
        const response = await fetch("index.php", { method: "POST", body: data });
        const result = await response.json();

        if (!response.ok) {
          throw new Error(result.error || "Κάτι πήγε στραβά!");
        }

        if (action === "register") {
          if (messageBox) {
            messageBox.style.color = "green";
            messageBox.innerHTML = "Επιτυχής εγγραφή! Μπορείς τώρα να συνδεθείς.";
          }
        } else if (result.redirect) {
          window.location.href = result.redirect;
        } else if (result.closeModal) {
          modal.style.display = "none";
        }
      } catch (error) {
        form.querySelectorAll("input[required]").forEach(i => i.classList.add("error"));
        if (messageBox) {
          messageBox.style.color = "red";
          messageBox.innerHTML = error.message;
        }
      } finally {
        isSubmitting = false;
      }
    });
  });
});
