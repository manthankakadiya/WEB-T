/* ==========================================================================
   StudentHub — Registration Form Validation
   ========================================================================== */

document.addEventListener("DOMContentLoaded", function () {

  var form = document.getElementById("registration-form");

  if (!form) return;

  var successBox = document.getElementById("reg-success-message");

  /* ================= REGEX PATTERNS ================= */

  var PATTERNS = {

    // First name + last name required
    name: /^[A-Za-z]+(?:['-][A-Za-z]+)*(?:\s+[A-Za-z]+(?:['-][A-Za-z]+)*)+$/,

    // Valid email
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,

    // Indian-style 10 digit mobile number
    mobile: /^[2-9][0-9]{9}$/,

    // Minimum 8 chars + uppercase + lowercase + number + special character
    password:
      /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+=-])[A-Za-z\d@$!%*?&#^()_+=-]{8,}$/

  };


  /* ================= FIELDS ================= */

  var fields = [

    /* NAME */
    {
      input: document.getElementById("reg-name"),
      error: document.getElementById("reg-name-error"),

      validate: function (value) {

        value = value.trim();

        if (value === "") {
          return "Full name is required.";
        }

        if (!PATTERNS.name.test(value)) {
          return "Please enter your first and last name using letters only.";
        }

        return "";
      }
    },


    /* EMAIL */
    {
      input: document.getElementById("reg-email"),
      error: document.getElementById("reg-email-error"),

      validate: function (value) {

        value = value.trim();

        if (value === "") {
          return "Email address is required.";
        }

        if (!PATTERNS.email.test(value)) {
          return "Please enter a valid email address, e.g. name@gmail.com.";
        }

        return "";
      }
    },


    /* MOBILE */
    {
      input: document.getElementById("reg-mobile"),
      error: document.getElementById("reg-mobile-error"),

      validate: function (value) {

        value = value.trim();

        if (value === "") {
          return "Mobile number is required.";
        }

        if (!PATTERNS.mobile.test(value)) {
          return "Please enter a valid 10-digit mobile number.";
        }

        return "";
      }
    },


    /* PASSWORD */
    {
      input: document.getElementById("password"),
      error: document.getElementById("password-error"),

      validate: function (value) {

        if (value === "") {
          return "Password is required.";
        }

        if (!PATTERNS.password.test(value)) {
          return "Password must contain 8+ characters, uppercase, lowercase, number and special character.";
        }

        return "";
      }
    },


    /* CONFIRM PASSWORD */
    {
      input: document.getElementById("confirm-password"),
      error: document.getElementById("confirm-password-error"),

      validate: function (value) {

        var password = document.getElementById("password").value;

        if (value === "") {
          return "Please confirm your password.";
        }

        if (value !== password) {
          return "Passwords do not match.";
        }

        return "";
      }
    }

  ];


  /* ================= VALIDATE FIELD ================= */

  function validateField(field) {

    if (!field.input || !field.error) {
      return true;
    }

    var message = field.validate(field.input.value);

    field.error.textContent = message;

    if (message !== "") {

      field.input.classList.add("invalid");
      field.input.classList.remove("valid");

      field.input.setAttribute("aria-invalid", "true");

      return false;

    } else {

      field.input.classList.remove("invalid");
      field.input.classList.add("valid");

      field.input.setAttribute("aria-invalid", "false");

      return true;
    }
  }


  /* ================= GENDER ================= */

  function validateGender() {

    var gender =
      form.querySelector('input[name="gender"]:checked');

    var error =
      document.getElementById("reg-gender-error");

    if (!gender) {

      error.textContent = "Please select your gender.";

      return false;
    }

    error.textContent = "";

    return true;
  }


  /* ================= TERMS ================= */

  function validateTerms() {

    var terms =
      document.getElementById("reg-terms");

    var error =
      document.getElementById("reg-terms-error");

    if (!terms.checked) {

      error.textContent =
        "Please accept the Terms & Conditions.";

      return false;
    }

    error.textContent = "";

    return true;
  }


  /* ================= LIVE VALIDATION ================= */

  fields.forEach(function (field) {

    if (!field.input) return;

    /* Validate when leaving field */
    field.input.addEventListener("blur", function () {

      validateField(field);

    });


    /* Remove/show error while typing */
    field.input.addEventListener("input", function () {

      if (
        field.input.classList.contains("invalid") ||
        field.input.classList.contains("valid")
      ) {

        validateField(field);

      }

    });

  });


  /* Gender */
  form
    .querySelectorAll('input[name="gender"]')
    .forEach(function (radio) {

      radio.addEventListener("change", validateGender);

    });


  /* Terms */
  var termsCheckbox =
    document.getElementById("reg-terms");

  if (termsCheckbox) {

    termsCheckbox.addEventListener(
      "change",
      validateTerms
    );

  }


  /* ================= FORM SUBMIT ================= */

  form.addEventListener("submit", function (e) {

    e.preventDefault();

    var allValid = true;
    var firstInvalidElement = null;


    /* Check Name, Email, Mobile, Password */
    fields.forEach(function (field) {

      if (!field.input) return;

      var valid = validateField(field);

      if (!valid && firstInvalidElement === null) {

        firstInvalidElement = field.input;

      }

      if (!valid) {
        allValid = false;
      }

    });


    /* Check Gender */

    var genderValid = validateGender();

    if (!genderValid) {

      allValid = false;

      if (!firstInvalidElement) {

        firstInvalidElement =
          form.querySelector('input[name="gender"]');

      }
    }


    /* Check Terms */

    var termsValid = validateTerms();

    if (!termsValid) {

      allValid = false;

      if (!firstInvalidElement) {

        firstInvalidElement = termsCheckbox;

      }
    }


    /* ================= INVALID FORM ================= */

    if (!allValid) {

      if (successBox) {
        successBox.textContent = "";
        successBox.classList.remove("show");
      }

      if (firstInvalidElement) {

        firstInvalidElement.focus();

      }

      return;
    }


    /* ================= SUCCESS ================= */

    var name =
      document
        .getElementById("reg-name")
        .value
        .trim();

    var firstName =
      name.split(" ")[0];


    /* SUCCESS POPUP */

    alert(
      "🎉 Registration Successful!\n\n" +
      "Welcome to StudentHub, " +
      firstName +
      "!"
    );


    /* Success message on page */

    if (successBox) {

      successBox.textContent =
        "🎉 Registration successful! Welcome to StudentHub, " +
        firstName +
        ".";

      successBox.classList.add("show");

    }


    /* Reset form */

    form.reset();


    /* Remove green/red borders */

    fields.forEach(function (field) {

      if (!field.input) return;

      field.input.classList.remove(
        "valid",
        "invalid"
      );

      field.input.removeAttribute(
        "aria-invalid"
      );

    });

  });

});