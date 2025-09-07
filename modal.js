document.addEventListener("DOMContentLoaded", () => {
  const authBtns = document.querySelectorAll(".auth-button-modal");
  const closeBtns = document.querySelectorAll(".modal .close");
  const rememberMeButton = document.getElementById('remember-me');
  const submitButtons = document.querySelectorAll(".modal .auth-button");
  let isSubmitting = false;
  let modal;
  let buttonType;

  // Open modal
  Array.prototype.forEach.call(authBtns, function(button) {
    button.addEventListener("click", () => {
      buttonType = button.getAttribute('data-type');
      if (buttonType == 'login') {
        modal = document.getElementById('loginModal');
      } else if (buttonType == 'register') {
        modal = document.getElementById('registerModal');
      } else if (buttonType == 'edit') {
        modal = document.getElementById('editModal');
        fetch("index.php?action=getUser")
        .then(response => response.json())
        .then(data => {
          const form = modal.querySelector('form');
          form.firstname.value = data.first_name;
          form.lastname.value = data.last_name;
          form.username.value = data.username;
          form.email.value = data.email;
          form.password.value = ""; // leave empty for security
        })
        .catch(err => {
          console.error("Error fetching user data", err);
        });
      }

      // Reset form and error message
      const form = modal.querySelector('form');
      form.reset();
      const messageBox = form.querySelector('.error-messagebox');
      if (messageBox) messageBox.innerHTML = "&nbsp;";

      // Reset input borders
      form.querySelectorAll("input").forEach(i => i.classList.remove("error"));

      modal.style.display = "flex";
    });
  });

  closeBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      btn.closest(".modal").style.display = "none";
    });
  });

  window.addEventListener("click", (event) => {
    if (modal && event.target === modal) {
      modal.style.display = "none";
    }
  });

  if (rememberMeButton) {
    rememberMeButton.addEventListener('click', function() {
      this.value = this.checked ? 1 : 0;
    });
  }

  if (submitButtons) {
    Array.prototype.forEach.call(submitButtons, function(button) {
      button.addEventListener('click', async () => {
        if (isSubmitting) return;
        isSubmitting = true;

        const form = document.getElementById(buttonType + 'Form');
        const messageBox = form.querySelector('.error-messagebox');

        if (messageBox) messageBox.innerHTML = "&nbsp;";
        form.querySelectorAll("input").forEach(i => i.classList.remove("error"));

        const data = new FormData(form);
        data.append("action", buttonType);

        try {
          const response = await fetch("index.php", { method: "POST", body: data });
          const result = await response.json();

          if (!response.ok) {
            throw new Error(result.error || "Κάτι πήγε στραβά!");
          }

          const messageBox = form.querySelector('.error-messagebox');

          if (buttonType === "register") {
            // Εμφάνιση μηνύματος επιτυχίας για εγγραφή
            if (messageBox) {
              messageBox.style.color = "green";  // <-- change text to green
              messageBox.innerHTML = "Επιτυχής εγγραφή! Μπορείς τώρα να συνδεθείς.";
            }
          } else if (result.redirect) {
            window.location.href = result.redirect;
          } else if (result.closeModal) {
            form.closest(".modal").style.display = "none";
            form.reset();
          }
        } catch (error) {
          // Highlight inputs and show error
          form.querySelectorAll("input[required]").forEach(i => i.classList.add("error"));
          if (messageBox) {
            messageBox.style.color = "red"; // ensure error is red
            messageBox.innerHTML = error.message;
          }
        } finally {
          isSubmitting = false;
        }
      });
    });
  }
});
