/**
 * Product Search Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchIcon = document.getElementById('search-icon');
    const searchDropdown = document.getElementById('search-dropdown');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    let searchTimeout;

    // Toggle search dropdown
    if (searchIcon && searchDropdown) {
        searchIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            searchDropdown.classList.toggle('open');
            if (searchDropdown.classList.contains('open')) {
                searchInput.focus();
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (searchDropdown && !searchDropdown.contains(e.target) && e.target !== searchIcon) {
            searchDropdown.classList.remove('open');
        }
    });

    // Handle search input with debounce
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = '<div class="search-message">Type at least 2 characters to search</div>';
                return;
            }

            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });

        // Handle Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            }
        });
    }

    // Perform AJAX search
    function performSearch(query) {
        searchResults.innerHTML = '<div class="search-message">Searching...</div>';

        fetch('search.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderResults(data.results);
                } else {
                    searchResults.innerHTML = '<div class="search-message">Error searching products</div>';
                }
            })
            .catch(error => {
                searchResults.innerHTML = '<div class="search-message">Error searching products</div>';
            });
    }

    // Render search results
    function renderResults(results) {
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-message">No products found</div>';
            return;
        }

        let html = '<div class="search-results-grid">';
        results.forEach(product => {
            html += `
                <a href="${product.url}" class="search-result-item">
                    <div class="search-result-image">
                        ${product.image ? `<img src="${product.image}" alt="${product.name}">` : '<div class="no-image">No image</div>'}
                    </div>
                    <div class="search-result-info">
                        <div class="search-result-name">${product.name}</div>
                        <div class="search-result-price">${product.price}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
        searchResults.innerHTML = html;
    }
});
