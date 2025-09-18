define(['jquery'], function($) {
    var ToggleModeMenu = {
        init: function() {
            var toggleButton = document.getElementById("menu-toggle");
            var modeMenu = document.getElementById("mod_readaloud_menubuttons_cont");

            // Initialise state from localStorage using the key "modeMenuOpen".
            var isOpen = localStorage.getItem('modeMenuOpen') === 'true';
            if (isOpen) {
                modeMenu.classList.remove("closed");
                toggleButton.setAttribute("aria-expanded", "true");
            } else {
                modeMenu.classList.add("closed");
                toggleButton.setAttribute("aria-expanded", "false");
            }

            // Toggle function for the mode menu.
            function toggleModeMenu() {
                var open = toggleButton.getAttribute("aria-expanded") === "true";
                if (open) {
                    // Close the mode menu.
                    modeMenu.classList.add("closed");
                    toggleButton.setAttribute("aria-expanded", "false");
                    localStorage.setItem('modeMenuOpen', false);
                } else {
                    // Open the mode menu.
                    modeMenu.classList.remove("closed");
                    toggleButton.setAttribute("aria-expanded", "true");
                    modeMenu.focus(); // Move focus to the menu for accessibility.
                    localStorage.setItem('modeMenuOpen', true);
                }
            }

            // Attach the click event listener to the toggle button.
            toggleButton.addEventListener("click", toggleModeMenu);

            // Responsive behavior: auto-close on screens narrower than 768px.
            function checkResponsive() {
                if (window.innerWidth < 768) {
                    modeMenu.classList.add("closed");
                    toggleButton.setAttribute("aria-expanded", "false");
                    localStorage.setItem('modeMenuOpen', false);
                }
            }
            window.addEventListener("resize", checkResponsive);

            // Listen for the end of CSS transitions.
            modeMenu.addEventListener('transitionend', function() {
                console.log("Mode Menu transition ended.");
            });

            // Show both passage and quiz columns
            document.getElementById('btnAll').addEventListener('click', function() {
                document.getElementById('quizpassage').style.display = 'block';
                document.getElementById('quizquestion').style.display = 'block';
                document.querySelector('.mod_readaloud_passage_cont').classList.remove('hide');
            });

            // Show only the passage column
            document.getElementById('btnPassage').addEventListener('click', function() {
                document.getElementById('quizpassage').style.display = 'block';
                document.getElementById('quizquestion').style.display = 'none';
                document.querySelector('.mod_readaloud_passage_cont').classList.remove('hide');
            });

            // Show only the quiz column
            document.getElementById('btnQuiz').addEventListener('click', function() {
                document.getElementById('quizpassage').style.display = 'none';
                document.getElementById('quizquestion').style.display = 'block';
            });
        }
    };

    return ToggleModeMenu;
});
