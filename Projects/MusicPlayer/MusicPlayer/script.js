document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const searchResults = document.getElementById('searchResults');
            const audioPlayer = document.getElementById('audioPlayer');
            const playBtn = document.getElementById('playBtn');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const likeBtn = document.getElementById('likeBtn');
            const progressBar = document.getElementById('progressBar');
            const progressFill = document.getElementById('progressFill');
            const currentTimeEl = document.getElementById('currentTime');
            const durationEl = document.getElementById('duration');
            const songTitle = document.getElementById('songTitle');
            const songArtist = document.getElementById('songArtist');
            const albumArt = document.getElementById('albumArt');
            const volumeSlider = document.getElementById('volumeSlider');

            let currentPlaylist = [];
            let currentIndex = 0;
            let isLiked = false;

            // Search functionality
            searchBtn.addEventListener('click', searchSongs);
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') searchSongs();
            });

        async function searchSongs() {
            const query = searchInput.value.trim();
            if (!query) {
                alert('Please enter a song or artist name');
                return;
            }

            searchResults.innerHTML = '<div class="loading">Searching...</div>';

            try {
                const response = await fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(query)}&entity=song&limit=10&media=music`);
                
                if (!response.ok) {
                    throw new Error('Search failed');
                }
                
                const data = await response.json();

                if (!data.results || data.results.length === 0) {
                    searchResults.innerHTML = '<div class="loading">No results found. Try a different search term.</div>';
                    return;
                }

                currentPlaylist = data.results;
                displayResults(data.results);
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="loading">Error searching. Please check your connection and try again.</div>';
            }
        }

        function displayResults(results) {
            searchResults.innerHTML = results.map((song, index) => `
                <div class="result-item" data-index="${index}">
                    <img src="${song.artworkUrl60}" alt="${song.trackName}" class="result-thumb">
                    <div class="result-info">
                        <div class="result-title">${song.trackName}</div>
                        <div class="result-artist">${song.artistName}</div>
                    </div>
                </div>
            `).join('');

            document.querySelectorAll('.result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const index = parseInt(item.dataset.index);
                    playSong(index);
                });
            });
        }

        function playSong(index) {
            currentIndex = index;
            const song = currentPlaylist[index];
            
            audioPlayer.src = song.previewUrl;
            songTitle.textContent = song.trackName;
            songArtist.textContent = song.artistName;
            albumArt.src = song.artworkUrl100.replace('100x100', '500x500');
            
            audioPlayer.play();
            updatePlayButton(true);
        }

        // Play/Pause
        playBtn.addEventListener('click', () => {
            if (audioPlayer.paused) {
                audioPlayer.play();
                updatePlayButton(true);
            } else {
                audioPlayer.pause();
                updatePlayButton(false);
            }
        });

        function updatePlayButton(isPlaying) {
            playBtn.innerHTML = isPlaying 
                ? '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>'
                : '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
        }

        // Previous/Next
        prevBtn.addEventListener('click', () => {
            if (currentPlaylist.length === 0) return;
            currentIndex = (currentIndex - 1 + currentPlaylist.length) % currentPlaylist.length;
            playSong(currentIndex);
        });

        nextBtn.addEventListener('click', () => {
            if (currentPlaylist.length === 0) return;
            currentIndex = (currentIndex + 1) % currentPlaylist.length;
            playSong(currentIndex);
        });

        // Like button
        likeBtn.addEventListener('click', () => {
            isLiked = !isLiked;
            likeBtn.classList.toggle('liked', isLiked);
        });

        // Progress bar
        audioPlayer.addEventListener('timeupdate', () => {
            const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
            progressFill.style.width = progress + '%';
            currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
        });

        audioPlayer.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(audioPlayer.duration);
        });

        progressBar.addEventListener('click', (e) => {
            const rect = progressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audioPlayer.currentTime = percent * audioPlayer.duration;
        });

        // Volume
        volumeSlider.addEventListener('input', (e) => {
            audioPlayer.volume = e.target.value / 100;
        });

        audioPlayer.volume = 0.7;

        // Auto-play next
        audioPlayer.addEventListener('ended', () => {
            if (currentPlaylist.length > 0) {
                currentIndex = (currentIndex + 1) % currentPlaylist.length;
                playSong(currentIndex);
            }
        });

        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        });