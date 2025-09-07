// render.js
document.addEventListener("DOMContentLoaded", () => {

    const profileIconLink = document.querySelector('a.profile-icon');
    const contentContainer = document.getElementById('content');

    // Handles fetching and injecting the profile page content
    function loadProfilePage() {
        if (!contentContainer) {
            console.error('Content container element not found.');
            return;
        }

        fetch('profile.html')
            .then(response => {
                if (!response.ok) throw new Error('Failed to load profile.html');
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const profileDocument = parser.parseFromString(html, 'text/html');
                const profileMainContent = profileDocument.querySelector('main.profile-container');
                const editModalContent = profileDocument.getElementById('editModal');
                
                if (profileMainContent) {
                    contentContainer.className = 'profile-container';
                    contentContainer.innerHTML = profileMainContent.innerHTML;
                    
                    // If the edit modal isn't already in the main document, add it.
                    if (editModalContent && !document.getElementById('editModal')) {
                        document.body.appendChild(editModalContent);
                    }

                    fetchAndDisplayUserData();
                    initializeProfileInteractivity(); // Set up listeners for the new content
                } else {
                    console.error('<main class="profile-container"> not found in profile.html');
                }
            })
            .catch(error => {
                console.error('Error loading profile page:', error);
            });
    }

    // Handles fetching user data from the server and updating the DOM
    function fetchAndDisplayUserData() {
        // Fetch user details
        fetch("index.php?action=getUser")
            .then(response => {
                if (!response.ok) {
                    window.location.href = 'index.html';
                    throw new Error("User not logged in or session expired.");
                }
                return response.json();
            })
            .then(data => {
                document.getElementById("first-name").textContent = data.first_name;
                document.getElementById("last-name").textContent = data.last_name;
                document.getElementById("username").textContent = data.username;
                document.getElementById("email").textContent = data.email;
            })
            .catch(err => {
                console.error("Error fetching user data:", err);
            });

        // Fetch user playlists
        fetch("index.php?action=getMyPlaylists")
            .then(response => response.json())
            .then(playlists => {
                const playlistsGrid = document.getElementById("my-playlists-grid");
                if (!playlistsGrid) return;

                playlistsGrid.innerHTML = ''; // Clear placeholder or old content

                if (playlists.length === 0) {
                    playlistsGrid.innerHTML = '<p>You haven\'t created any playlists yet. Go to "Οι Λίστες μου" to create one!</p>';
                } else {
                    playlists.forEach(playlist => {
                        const playlistCard = document.createElement('a');
                        playlistCard.className = 'playlist-card';
                        playlistCard.href = `view_playlist.php?id=${playlist.id}`;
                        
                        const playlistName = document.createElement('h3');
                        playlistName.textContent = playlist.name;
                        
                        playlistCard.appendChild(playlistName);
                        playlistsGrid.appendChild(playlistCard);
                    });
                }
            })
            .catch(err => {
                console.error("Error fetching playlists:", err);
                const playlistsGrid = document.getElementById("my-playlists-grid");
                if(playlistsGrid) {
                    playlistsGrid.innerHTML = '<p>Could not load playlists.</p>';
                }
            });
    }

    // Sets up event listeners for the dynamically loaded profile page content
    function initializeProfileInteractivity() {
        const editButton = document.querySelector('.auth-button-modal[data-type="edit"]');
        const editModal = document.getElementById('editModal');
        
        if (!editButton || !editModal) {
            console.error('Edit button or modal not found on the page.');
            return;
        }

        const closeButton = editModal.querySelector('.close');
        const form = editModal.querySelector('form');
        const submitButton = form.querySelector('.auth-button');
        const messageBox = form.querySelector('.error-messagebox');
        let isSubmitting = false;

        // Open the modal and pre-fill with user data
        editButton.addEventListener('click', () => {
            fetch("index.php?action=getUser")
                .then(response => response.json())
                .then(data => {
                    form.firstname.value = data.first_name;
                    form.lastname.value = data.last_name;
                    form.username.value = data.username;
                    form.email.value = data.email;
                    form.password.value = ""; // leave empty for security
                })
                .catch(err => console.error("Error fetching user data", err));
            
            messageBox.innerHTML = "&nbsp;"; // Clear previous messages
            editModal.style.display = 'flex';
        });

        // Close the modal
        closeButton.addEventListener('click', () => {
            editModal.style.display = 'none';
        });

        window.addEventListener("click", (event) => {
            if (event.target === editModal) {
                editModal.style.display = "none";
            }
        });

        // Handle form submission
        submitButton.addEventListener('click', async () => {
            if (isSubmitting) return;
            isSubmitting = true;

            messageBox.innerHTML = "&nbsp;";
            const data = new FormData(form);
            data.append("action", "edit");

            try {
                const response = await fetch("index.php", { method: "POST", body: data });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || "An unknown error occurred.");
                }
                
                // On success, update the profile details on the main page
                if (result.success && result.userData) {
                    document.getElementById("first-name").textContent = result.userData.first_name;
                    document.getElementById("last-name").textContent = result.userData.last_name;
                    document.getElementById("username").textContent = result.userData.username;
                    document.getElementById("email").textContent = result.userData.email;

                    // Show success message and close modal
                    messageBox.style.color = "green";
                    messageBox.innerHTML = "Profile updated successfully!";
                    setTimeout(() => {
                        editModal.style.display = "none";
                        messageBox.style.color = "red"; // reset color for future errors
                    }, 2000);
                }

            } catch (error) {
                messageBox.style.color = "red";
                messageBox.innerHTML = error.message;
            } finally {
                isSubmitting = false;
            }
        });
    }

    // Initial listener for the main profile icon
    if (profileIconLink) {
        profileIconLink.addEventListener('click', (event) => {
            event.preventDefault();
            loadProfilePage();
        });
    }
});
