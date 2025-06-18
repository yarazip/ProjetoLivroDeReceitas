document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("theme-toggle");
    const body = document.body;
    const moonIcon = toggleButton.querySelector(".fa-moon");
    const sunIcon = toggleButton.querySelector(".fa-sun");

    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        body.classList.add("dark-mode");
        moonIcon.style.display = "none";
        sunIcon.style.display = "inline";
    } else {
        body.classList.remove("dark-mode");
        moonIcon.style.display = "inline";
        sunIcon.style.display = "none";
    }

    toggleButton.addEventListener("click", function () {
        body.classList.toggle("dark-mode");

        const isDark = body.classList.contains("dark-mode");
        localStorage.setItem("theme", isDark ? "dark" : "light");

        moonIcon.style.display = isDark ? "none" : "inline";
        sunIcon.style.display = isDark ? "inline" : "none";
    });
});
