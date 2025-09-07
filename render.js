// render.js
document.addEventListener("DOMContentLoaded", () => {

    const profileIconLink = document.querySelector('a.profile-icon');
    const contentContainer = document.getElementById('content');
    const logoutButton = document.getElementById('logout-btn');

    // Listener for Profile Icon click
    if (profileIconLink) {
        profileIconLink.addEventListener('click', (event) => {
            event.preventDefault();
            loadProfilePage();
        });
    }

    // Listener for Logout Button click
    if (logoutButton) {
        logoutButton.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                const response = await fetch("index.php?action=logout");
                const result = await response.json();
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } catch (error) {
                console.error('Logout failed:', error);
            }
        });
    }

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
                const deleteModalContent = profileDocument.getElementById('deleteModal');
                
                if (profileMainContent) {
                    contentContainer.className = 'profile-container'; // Keep class consistency
                    contentContainer.innerHTML = profileMainContent.innerHTML;
                    
                    if (editModalContent && !document.getElementById('editModal')) {
                        document.body.appendChild(editModalContent);
                    }
                     if (deleteModalContent && !document.getElementById('deleteModal')) {
                        document.body.appendChild(deleteModalContent);
                    }

                    fetchAndDisplayUserData();
                    fetchAndDisplayUserPlaylists();
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
        fetch("index.php?action=getUser")
            .then(response => {
                if (!response.ok) {
                    if (response.status === 401 || response.status === 403) window.location.href = 'index.html';
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
    }

    function fetchAndDisplayUserPlaylists() {
        fetch("index.php?action=getMyPlaylists")
            .then(response => response.json())
            .then(playlists => {
                const playlistsGrid = document.getElementById("my-playlists-grid");
                if (!playlistsGrid) return;

                if (playlists.length === 0) {
                    playlistsGrid.innerHTML = '<p>You have not created any playlists yet.</p>';
                    return;
                }

                playlistsGrid.innerHTML = '';
                playlists.forEach(playlist => {
                    const card = document.createElement('a');
                    card.href = `view_playlist.php?id=${playlist.id}`;
                    card.className = 'playlist-card';
                    card.innerHTML = `<h3>${playlist.name}</h3>`;
                    playlistsGrid.appendChild(card);
                });
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
        const deleteButton = document.getElementById('delete-profile-btn');
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (!editButton || !editModal || !deleteButton || !deleteModal) {
            console.error('An interactive profile element was not found on the page.');
            return;
        }

        // --- Edit Modal Logic ---
        const closeEditButton = editModal.querySelector('.close');
        const editForm = editModal.querySelector('form');
        const submitEditButton = editForm.querySelector('.auth-button');
        const editMessageBox = editForm.querySelector('.error-messagebox');
        let isSubmitting = false;

        editButton.addEventListener('click', () => {
            fetch("index.php?action=getUser")
                .then(response => response.json())
                .then(data => {
                    editForm.firstname.value = data.first_name;
                    editForm.lastname.value = data.last_name;
                    editForm.username.value = data.username;
                    editForm.email.value = data.email;
                    editForm.password.value = "";
                })
                .catch(err => console.error("Error fetching user data", err));
            
            editMessageBox.innerHTML = "&nbsp;";
            editModal.style.display = 'flex';
        });

        closeEditButton.addEventListener('click', () => {
            editModal.style.display = 'none';
        });

        // --- Delete Modal Logic ---
        const closeDeleteButton = deleteModal.querySelector('.close');
        const deleteForm = deleteModal.querySelector('form');
        const confirmDeleteButton = document.getElementById('confirm-delete-btn');
        const deleteMessageBox = deleteForm.querySelector('.error-messagebox');
        let isDeleting = false;

        deleteButton.addEventListener('click', () => {
            deleteForm.reset();
            deleteMessageBox.innerHTML = "&nbsp;";
            deleteModal.style.display = 'flex';
        });

        closeDeleteButton.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });

        window.addEventListener("click", (event) => {
            if (event.target === editModal) editModal.style.display = "none";
            if (event.target === deleteModal) deleteModal.style.display = "none";
        });

        // Handle Edit Form Submission
        submitEditButton.addEventListener('click', async () => {
            if (isSubmitting) return;
            isSubmitting = true;

            editMessageBox.innerHTML = "&nbsp;";
            const data = new FormData(editForm);
            data.append("action", "edit");

            try {
                const response = await fetch("index.php", { method: "POST", body: data });
                const result = await response.json();

                if (!response.ok) throw new Error(result.error || "An unknown error occurred.");
                
                if (result.success && result.userData) {
                    document.getElementById("first-name").textContent = result.userData.first_name;
                    document.getElementById("last-name").textContent = result.userData.last_name;
                    document.getElementById("username").textContent = result.userData.username;
                    document.getElementById("email").textContent = result.userData.email;

                    editMessageBox.style.color = "green";
                    editMessageBox.innerHTML = "Profile updated successfully!";
                    setTimeout(() => {
                        editModal.style.display = "none";
                        editMessageBox.style.color = "red";
                    }, 2000);
                }

            } catch (error) {
                editMessageBox.style.color = "red";
                editMessageBox.innerHTML = error.message;
            } finally {
                isSubmitting = false;
            }
        });

        // Handle Delete Form Submission
        confirmDeleteButton.addEventListener('click', async () => {
            if (isDeleting) return;
            isDeleting = true;
            confirmDeleteButton.textContent = 'DELETING...';

            deleteMessageBox.innerHTML = '&nbsp;';
            const data = new FormData(deleteForm);
            data.append('action', 'deleteUser');

            try {
                const response = await fetch('index.php', { method: 'POST', body: data });
                const result = await response.json();

                if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');

                if (result.redirect) {
                    // A simple message before redirecting
                    deleteMessageBox.style.color = 'green';
                    deleteMessageBox.innerHTML = 'Account deleted successfully. Redirecting...';
                    setTimeout(() => {
                         window.location.href = result.redirect;
                    }, 1500);
                }
            } catch (error) {
                deleteMessageBox.style.color = 'red';
                deleteMessageBox.innerHTML = error.message;
            } finally {
                isDeleting = false;
                confirmDeleteButton.textContent = 'CONFIRM DELETION';
            }
        });
    }
});

